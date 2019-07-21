<?php
declare(strict_types=1);

namespace App\XenForo;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use App\XenForo\Auth;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    private $entityManager;
    private $router;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $security;

    private $_key = 'path.afterlogin';

    public function __construct(Auth $auth, EntityManagerInterface $entityManager, RouterInterface $router, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->security = $security;
        $this->auth = $auth;
    }

    public function supports(Request $request): bool
    {
        if ('security_login' !== $request->attributes->get('_route')) {
            return false;
        }
        if ($request->isMethod('GET')) {
            $path = '/';
            if ($request->query->get('redirect_to') !== null) {
                $path = $request->query->get('redirect_to');
            }
            if ($request->headers->get('referer') !== null) {
                $path = $request->headers->get('referer');
            }
            $this->saveTargetPath($request->getSession(), $this->_key, $path);
        }
        return $request->isMethod('POST');
    }

    public function getCredentials(Request $request): array
    {
        $credentials = [
            'username' => $request->request->get('_username'),
            'password' => $request->request->get('_password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );
        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider): User
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }
        try {
            $this->auth->setCridentials($credentials)->login();
            $this->auth->refreshUser($userProvider);   
        } catch(Exception $e) {
            throw new CustomUserMessageAuthenticationException('Invalid username or password');
        }
        $user = $userProvider->loadUserByUsername($credentials['username']);
        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Invalid username or password');            
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): RedirectResponse
    {
        // dd($request->getSession());
        // dd($this->getTargetPath($request->getSession(), $providerKey));
        // dd($this->security->isGranted('ROLE_USER'));
        // dd($token->getRoleNames());
        if ($targetPath = $this->getTargetPath($request->getSession(), $this->_key)) {
            return new RedirectResponse($targetPath);
        }
        return new RedirectResponse($this->router->generate('blog_index'));
    }

    protected function getLoginUrl(): string
    {
        return $this->router->generate('security_login');
    }
}



// public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
// {
//     try {
//         $user = $userProvider->loadUserByUsername($token->getUsername());
//     } catch (UsernameNotFoundException $exception) {
//         // CAUTION: this message will be returned to the client
//         // (so don't put any un-trusted messages / error strings here)
//         throw new CustomUserMessageAuthenticationException('Invalid username or password');
//     }

//     $currentUser = $token->getUser();

//     if ($currentUser instanceof UserInterface) {
//         if ($currentUser->getPassword() !== $user->getPassword()) {
//             throw new BadCredentialsException('The credentials were changed from another session.');
//         }
//     } else {
//         if ('' === ($givenPassword = $token->getCredentials())) {
//             throw new BadCredentialsException('The given password cannot be empty.');
//         }
//         if (!$this->encoderFactory->getEncoder($user)->isPasswordValid($user->getPassword(), $givenPassword, $user->getSalt())) {
//             throw new BadCredentialsException('The given password is invalid.');
//         }
//     }

//     if ($isPasswordValid) {
//         $currentHour = date('G');
//         if ($currentHour < 14 || $currentHour > 16) {
//             // CAUTION: this message will be returned to the client
//             // (so don't put any un-trusted messages / error strings here)
//             throw new CustomUserMessageAuthenticationException(
//                 'You can only log in between 2 and 4!',
//                 array(), // Message Data
//                 412 // HTTP 412 Precondition Failed
//             );
//         }

//         return new UsernamePasswordToken(
//             $user,
//             $user->getPassword(),
//             $providerKey,
//             $user->getRoles()
//         );
//     }

//     // CAUTION: this message will be returned to the client
//     // (so don't put any un-trusted messages / error strings here)
//     throw new CustomUserMessageAuthenticationException('Invalid username or password');
// }
