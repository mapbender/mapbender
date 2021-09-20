<?php

namespace FOM\UserBundle\Component;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use FOM\UserBundle\Component\Ldap;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\User;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

/**
 * Provides user and group identities available for ACL assignments
 * Service registered as fom.identities.provider
 */
class FOMIdentitiesProvider implements IdentitiesProviderInterface
{
    /** @var ManagerRegistry */
    protected $doctrineRegistry;
    /** @var Ldap\UserProvider */
    protected $ldapUserIdentitiesProvider;
    /** @var string */
    protected $userEntityClass;

    public function __construct(ManagerRegistry $doctrineRegistry,
                                Ldap\UserProvider $ldapUserProvider,
                                $userEntityClass)

    {
        $this->doctrineRegistry = $doctrineRegistry;
        $this->ldapUserIdentitiesProvider = $ldapUserProvider;
        $this->userEntityClass = $userEntityClass;
    }

    /**
     * @return ManagerRegistry
     */
    final protected function getDoctrine()
    {
        return $this->doctrineRegistry;
    }

    /**
     * @param string $entityName
     * @return ObjectRepository
     */
    protected function getRepository($entityName)
    {
        return $this->doctrineRegistry->getRepository($entityName);
    }

    /**
     * @return Group[]
     */
    public function getAllGroups()
    {
        $repo = $this->getRepository('FOMUserBundle:Group');
        return $repo->findAll();
    }

    /**
     * @return UserSecurityIdentity[]
     */
    public function getLdapUsers()
    {
        return $this->ldapUserIdentitiesProvider->getIdentities('*');
    }

    /**
     * @return UserSecurityIdentity[]
     */
    public function getDataBaseUserIdentities()
    {
        $identities = array();
        foreach ($this->getDatabaseUsers() as $user) {
            $identities[] = UserSecurityIdentity::fromAccount($user);
        }
        return $identities;
    }

    /**
     * @return User[]
     */
    public function getDatabaseUsers()
    {
        return $this->getUserRepository()->findAll();
    }

    /**
     * @return UserSecurityIdentity[]
     */
    public function getAllUsers()
    {
        return array_merge($this->getLdapUsers(), $this->getDataBaseUserIdentities());
    }

    /**
     * @return ObjectRepository
     */
    public function getUserRepository()
    {
        return $this->doctrineRegistry->getRepository($this->userEntityClass);
    }
}
