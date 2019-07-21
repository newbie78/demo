<?php
declare(strict_types=1);

namespace App\XenForo;

use stdClass;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use App\Entity\User;
use App\XenForo\Api;

class Auth
{

    private $passwordEncoder;
    private $_em;
    private $_api;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->_em = $entityManager;
        $this->_api = new Api();
    }

    public function setCridentials(array $credentials): self
    {
        $this->_credentials = $credentials;
        return $this;
    }

    public function login(): bool
    {
        if (!$this->_api
        ->setType('POST')
        ->setPath('auth/')
        ->send([
            'login' => $this->_credentials['username'],
            'password' => $this->_credentials['password'],
        ])) {
            return false;
        }
        return true;
    }

    public function refreshUser(UserProviderInterface $userProvider): void
    {
        $localUser = $userProvider->loadUserByUsername($this->_credentials['username']);
        // dd($localUser);
        $xenforoUser = $this->getUser();
        // $xenforoUser->user_id
        // $xenforoUser->avatar_urls->0
        if ($localUser === null) {
            $user = new User();
            $user->setFullName($xenforoUser->username);
            $user->setUsername($xenforoUser->username);
            $user->setPassword($this->passwordEncoder->encodePassword($user, $this->_credentials['password']));
            $user->setEmail($xenforoUser->email);
            $user->setRoles(['ROLE_USER']);
        } else {
            $user = $localUser;
            $user->setFullName($xenforoUser->username);
            $user->setUsername($xenforoUser->username);
            $user->setEmail($xenforoUser->email);
        }
        $this->_em->persist($user);
        $this->_em->flush();
    }

    private function getUser(): stdClass
    {
        if (!$this->_api->getBody()->success) {
            //exept
        }
        return $this->_api->getBody()->user;
    }
}
