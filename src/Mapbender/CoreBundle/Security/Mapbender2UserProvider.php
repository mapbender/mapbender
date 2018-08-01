<?php

namespace Mapbender\CoreBundle\Security;

use Doctrine\DBAL\Driver\Connection;
use PDO;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * User provider for Mapbender2 databases
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 * @deprecated Nowhere used. Will be removed in release/3.0.7
 */
class Mapbender2UserProvider implements UserProviderInterface
{
    /** @var Connection */
    private $connection;

    /**
     * Mapbender2UserProvider constructor.
     *
     * @param Connection $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $username
     * @return User|void
     * @deprecated User class isn't defined. Miss implemented.
     */
    public function loadUserByUsername($username)
    {
        $sql       = "SELECT * FROM mb_user WHERE mb_user_name = :name";
        $statement = $this->connection->prepare($sql);
        $statement->bindValue('name', $username);
        $statement->execute();
        $user_data = $statement->fetch(PDO::FETCH_ASSOC);
        if ($user_data === false) {
            throw new UsernameNotFoundException(sprintf('User %s can not be found.', $username));
            return;
        }

        $user = new User($username,
            $user_data['mb_user_password'],
            $user_data['mb_user_email'],
            $user_data['mb_user_realname']);
        $user->setExtraData($user_data);
        return $user;
    }

    /**
     * @param UserInterface $user
     * @return User|void
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }
        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return true;
    }
}

