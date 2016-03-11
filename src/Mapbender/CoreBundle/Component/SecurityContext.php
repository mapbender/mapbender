<?php
/**
 *
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 19.02.2015 by WhereGroup GmbH & Co. KG
 */
namespace Mapbender\CoreBundle\Component;

use FOM\UserBundle\Component\User\UserEntityInterface;
use FOM\UserBundle\Entity\User;

/**
 * Class SecurityContext
 *
 * @package   FOM\UserBundle\Component
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2015 by WhereGroup GmbH & Co. KG
 */
class SecurityContext extends \Symfony\Component\Security\Core\SecurityContext
{
    /**
     * Get current logged user by the token
     *
     * @return UserEntityInterface
     */
    public function getUser()
    {
        /** @var User $user */
        $user = $this->getToken()->getUser();
        if (!is_object($user) && is_string($user) && $user == 'anon.') {
            $user = new User();
            $user->setUsername("anon.");
        }
        return $user;
    }

    /**
     * Get current user role list
     *
     * @return array Role name list
     */
    public function getRolesAsArray()
    {
        $userRoles = $this->getToken()->getRoles();
        $temp      = array();
        foreach ($userRoles as $role) {
            $temp[] = $role->getRole();
        }
        return $temp;
    }
}