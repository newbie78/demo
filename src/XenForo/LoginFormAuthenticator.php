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
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\XenForo\Auth;
use App\XenForo\Exception\WhenReceivingUserException;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    private $entityManager;
    private $router;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $security;
    private $_params;

    public function __construct(ParameterBagInterface $params, Auth $auth, EntityManagerInterface $entityManager, RouterInterface $router, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, Security $security)
    {
        $this->_params = $params;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->security = $security;
        $this->auth = $auth;
    }

    public function supports(Request $request): bool
    {
        if ($this->_params->get('xenforo.auth.login_route') !== $request->attributes->get('_route')) {
            return false;
        }
        if ($request->isMethod('GET')) {
            $path = $this->_params->get('xenforo.auth.redirect_default');
            $redirectVar = $this->_params->get('xenforo.auth.redirect_variable_inquery');
            if ($request->query->get($redirectVar) !== null) {
                $path = $request->query->get($redirectVar);
            }
            if ($request->headers->get('referer') !== null) {
                $path = $request->headers->get('referer');
            }
            $this->saveTargetPath($request->getSession(), $this->_params->get('xenforo.auth.redirect_key'), $path);
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
        // try {
        //     $user = $userProvider->loadUserByUsername($credentials['username']);
        //     if ($user->getRoles() !== $this->_params->get('xenforo.auth.roles')) {
        //         return $user;
        //     }
        // } catch(UsernameNotFoundException $e) {
        //     // do nothing
        // }
        try {
            $this->auth->setCridentials($credentials)->login();
            $this->auth->refreshUser($userProvider);
        } catch(WhenReceivingUserException $e) {
            throw new CustomUserMessageAuthenticationException($this->_params->get('xenforo.auth.notfound_message'));
        }
        try {
            $user = $userProvider->loadUserByUsername($credentials['username']);
            return $user;
        } catch(UsernameNotFoundException $e) {
            throw new CustomUserMessageAuthenticationException($this->_params->get('xenforo.auth.notfound_message'));
        }
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): RedirectResponse
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $this->_params->get('xenforo.auth.redirect_key'))) {
            return new RedirectResponse($targetPath);
        }
        return new RedirectResponse($this->_params->get('xenforo.auth.redirect_default'));
    }

    protected function getLoginUrl(): string
    {
        return $this->router->generate($this->_params->get('xenforo.auth.login_route'));
    }
}
