<?php

namespace Mapbender\DrupalIntegrationBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;


class DrupalUser implements UserInterface
{
    private $uid;
    private $username;
    private $password;
    private $email;
    private $roles;

    public function __construct($user)
    {
        $this->uid = $user->uid;
        if($user->uid != 0) {
            $this->username = $user->name;
            $this->password = $user->pass;
            $this->email = $user->mail;
        }
        $this->roles = $user->roles;
    }

    public function getId()
    {
        return $this->uid;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return '';
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function eraseCredentials()
    {
    }

    public function equals(UserInterface $user)
    {
        if (!$user instanceof DrupalUser) {
            return false;
        }

        if($this->id !== $user->getId()) {
            return false;
        }

        return true;
    }
}
