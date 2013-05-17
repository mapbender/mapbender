<?php

namespace Mapbender\ManagerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Mapbender\CoreBundle\Component\MapbenderBundle;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

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
                ),
                'subroutes' => array(
                    array('title'=>'New Application',
                          'route'=>'mapbender_manager_application_new',
                          'enabled' => function($securityContext) {
                                $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application');
                                return $securityContext->isGranted('CREATE', $oid);
                          })
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
                    array('title'=>'Add Service',
                           'route'=>'mapbender_manager_repository_new',
                           'enabled' => function($securityContext) {
                                $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
                                return $securityContext->isGranted('CREATE', $oid);
                            })
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
