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
