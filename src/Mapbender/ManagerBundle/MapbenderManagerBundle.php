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
                'weight' => 10,
                'title' => 'Applications',
                'route' => 'mapbender_manager_application_index',
                'routes' => array(
                    'mapbender_manager_application',
                )
            ),
            array(
                'weight' => 20,
                'title' => 'Services',
                'route' => 'mapbender_manager_repository_index',
                'routes' => array(
                    'mapbender_manager_repository',
                )
            ),
        );
    }
}
