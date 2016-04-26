<?php

namespace Mapbender\DrupalIntegrationBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;


class DrupalUserProvider implements UserProviderInterface
{
    public function loadUserByUsername($username)
    {
        $drupalUser = user_load_by_name($username);
        if($drupalUser !== False) {
            return new DrupalUser($drupalUser);
        }

        throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof DrupalUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Mapbender\DrupalIntegrationBundle\Security\User\DrupalUser';
    }
}
