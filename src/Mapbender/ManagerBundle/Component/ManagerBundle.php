<?php

namespace Mapbender\ManagerBundle\Component;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * ManagerBundle base class.
 *
 * This class is the base class for bundles implementing Manager functionality.
 *
 * @author Christian Wygoda
 *
 * Copied into Mapbender from FOM v3.0.6.3
 * see https://github.com/mapbender/fom/blob/v3.0.6.3/src/FOM/ManagerBundle/Component/ManagerBundle.php
 */
class ManagerBundle extends Bundle
{
    /**
     * Getter for list of controllers to embed into Manager interface.
     *
     * The list must be an array of arrays, each giving the integer weight, name, route and array of route prefixes
     * to match against. See source for an example.
     *
     * return array(
     *      array(
     *          weight => 5,
     *          name => 'Users',
     *          route => 'fom_user_useranager_index',
     *          routes => array(
     *              'fom_user_usermanager',
     *              'fom_user_rolemanager'
     *          )
     *      )
     *  );
     *
     * @return array[]
     */
    public function getManagerControllers()
    {
        return array();
    }

    /**
     * Getter for all available roles a bundles defines.
     *
     * The returned array must be a mapping of role id strings (e.g. "ROLE_USER_ADMIN") to displayable
     * role descriptions (e.g. "Can administrate users")
     *
     * @return string[] roles
     */
    public function getRoles()
    {
        return array();
    }

    /**
     * Return a mapping of acl class names to displayable descriptions. E.g.
     * "FOM\UserBundle\Entity\Group" => "Groups"
     *
     * @return string[]
     */
    public function getACLClasses()
    {
        return array();
    }
}
