<?php

namespace Mapbender\ManagerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Mapbender\CoreBundle\Component\MapbenderBundle;

class MapbenderManagerBundle extends MapbenderBundle
{
    public function getAdminControllers()
    {
        return array(
            array(
                'weight' => 0,
                'title' => 'Applications',
                'route' => 'mapbender_manager_application_index',
                'controllers' => array(
                    'mapbender_manager_application',
                )
            ),
            array(
                'weight' => 10,
                'title' => 'Services',
                'route' => 'mapbender_manager_layer_index',
                'controllers' => array(
                    'mapbender_manager_layer',
                    'mapbender_manager_repository',
                )
            ),
            array(
                'weight' => 20,
                'title' => 'Users',
                'route' => 'mapbender_manager_user_index',
                'controllers' => array(
                    'mapbender_manager_user',
                    'mapbender_manager_role'
                )
            )
        );
    }
}
