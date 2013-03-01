<?php

namespace Mapbender\ManagerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Mapbender\CoreBundle\Component\MapbenderBundle;

class MapbenderManagerBundle extends MapbenderBundle
{
    public function getManagerControllers()
    {
        return array(
            array(
                'weight' => 0,
                'title' => 'Startpage',
                'route' => 'mapbender_start',
                'routes' => array('mapbender_start'),
                'subroutes' => array()
            ),
            array(
                'weight' => 10,
                'title' => 'Applications',
                'route' => 'mapbender_manager_application_index',
                'routes' => array(
                    'mapbender_manager_application',
                ),
                'subroutes' => array(
                    array('title'=>'New Application', 
                          'route'=>'mapbender_manager_application_new')
                )
            ),
            array(
                'weight' => 20,
                'title' => 'Services',
                'route' => 'mapbender_manager_repository_index',
                'routes' => array(
                    'mapbender_manager_repository',
                ),
                'subroutes' => array(
                    0 => array('title'=>'Add Service', 
                               'route'=>'mapbender_manager_repository_new')
                )
            ),
        );
    }

    public function getRoles()
    {
        return array(
            'ROLE_ADMIN_MAPBENDER_APPLICATION'
                => 'Can administrate applications');
    }
}
