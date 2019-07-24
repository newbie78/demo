<?php
declare(strict_types=1);

namespace App\XenForo;

use stdClass;
use Throwable;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use App\Entity\User;
use App\XenForo\Api;
use App\XenForo\Exception\WhenReceivingUserException;

class Auth
{

    private $_encoder;
    private $_params;
    private $_em;
    private $_api;

    public function __construct(ParameterBagInterface $params, Api $api, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->_encoder = $passwordEncoder;
        $this->_params = $params;
        $this->_em = $entityManager;
        $this->_api = $api;
    }

    public function setCridentials(array $credentials): self
    {
        $this->_credentials = $credentials;
        return $this;
    }

    public function login(): void
    {
        try {
            $this->_api
            ->setType('POST')
            ->setPath('auth/')
            ->send([
                'login' => $this->_credentials['username'],
                'password' => $this->_credentials['password'],
            ]);
        } catch (RequestException | ServerException $e) {
            throw new WhenReceivingUserException();
        }
    }

    public function refreshUser(UserProviderInterface $userProvider): void
    {
        $xenforoUser = $this->getUser();
        if (!in_array($xenforoUser->user_group_id, array_keys($this->_params->get('xenforo.auth.roles')))) {
            throw new WhenReceivingUserException();
        }
        $roles[] = $this->_params->get('xenforo.auth.roles')[$xenforoUser->user_group_id];
        // dd($xenforoUser);
        try {
            $user = $userProvider->loadUserByUsername($this->_credentials['username']);
        } catch(UsernameNotFoundException $e) {
            $user = new User();
            $user->setPassword($this->_encoder->encodePassword($user, $this->_credentials['password']));
            $user->setRoles($roles);
        }
        $user->setFullName($xenforoUser->username);
        $user->setUsername($xenforoUser->username);
        $user->setEmail($xenforoUser->email);
        try {
            $user->setXenforoId($xenforoUser->user_id);
            $user->setAvatarPath($xenforoUser->avatar_urls->o);
        } catch(Throwable $e) {
            // do nothing
        }
        $this->_em->persist($user);
        $this->_em->flush();
    }

    private function getUser(): stdClass
    {
        if (!$this->_api->getBody()->success) {
            throw new WhenReceivingUserException();
        }
        return $this->_api->getBody()->user;
    }
}
