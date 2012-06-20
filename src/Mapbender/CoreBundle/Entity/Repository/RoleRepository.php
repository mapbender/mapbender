<?php

namespace Mapbender\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Entity\Role;
use Mapbender\CoreBundle\Entity\User;

/**
 * Custom role repository
 *
 * This repository handles all the magic like extra roles which are
 * automatically gained and updating the MPTT for the role hierarchy
 *
 * @author Christian Wygoda
 */
class RoleRepository extends EntityRepository {
    /**
     * Find all available roles
     *
     * @return array
     */
    public function findAllOrdered() {
        return $this->getEntityManager()
            ->createQuery('SELECT r FROM MapbenderCoreBundle:Role r ORDER BY r.title')
            ->getResult();
    }

    /**
     * Find all parent roles for given role
     *
     * @param Role $role Role to find parent for
     * @return array
     */
    public function findParentRoles(Role $role) {
        throw new \RuntimeException('NIY');
    }

    /**
     * Find all child roles for given role
     *
     * @param Role $role Role to find children for
     * @return array
     */
    public function findChildRoles(Role $role) {
        throw new \RuntimeException('NIY');
    }

    public function insertRole(Role $role, Role $parent = null, $position = null) {
        throw new \RuntimeException('NIY');
    }

    public function updateRole(Role $role, Role $parent = null, $position = null) {
        throw new \RuntimeException('NIY');
    }

    public function deleteRole(Role $role) {
        throw new \RuntimeException('NIY');
    }
}

