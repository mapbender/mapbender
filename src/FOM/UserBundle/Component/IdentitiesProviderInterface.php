<?php

namespace FOM\UserBundle\Component;

use FOM\UserBundle\Entity\Group;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

interface IdentitiesProviderInterface
{
    /**
     * Get all group objects
     * @return Group[]
     */
    public function getAllGroups();

    /**
     * Get all user identities. UserInterface objects also work, because values are filtered and normalized.
     * @return UserSecurityIdentity[]
     */
    public function getAllUsers();
}
