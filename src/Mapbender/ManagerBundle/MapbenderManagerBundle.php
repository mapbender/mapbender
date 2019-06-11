<?php

namespace Mapbender\ManagerBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\CoreBundle\DependencyInjection\Compiler\MapbenderYamlCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

class MapbenderManagerBundle extends MapbenderBundle
{

    public function build(ContainerBuilder $container)
    {
        $appFileDir = dirname(__FILE__) . '/Resources/config/applications';
        $container->addCompilerPass(new MapbenderYamlCompilerPass(realpath($appFileDir)));
    }

    public function getManagerControllers()
    {
        $trans = $this->container->get('translator');
        return array(
            array(
                'weight' => 10,
                'title' => $trans->trans("mb.manager.managerbundle.applications"),
                'route' => 'mapbender_manager_application_index',
                'routes' => array(
                    'mapbender_manager_application',
                ),
                'subroutes' => array(
                    array('title' => $trans->trans("mb.manager.managerbundle.new_application"),
                        'route' => 'mapbender_manager_application_new',
                        'enabled' => function($securityContext) {
                        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application');
                        return $securityContext->isGranted('CREATE', $oid);
                    }),
                    array('idx' => "application-export",
                        'title' => $trans->trans("mb.manager.managerbundle.export_application"),
                        'route' => 'mapbender_manager_application_export',
                        'enabled' => function($securityContext) {
                        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application');
                        return $securityContext->isGranted('CREATE', $oid);
                    }),
                    array('idx' => "application-import",
                        'title' => $trans->trans("mb.manager.managerbundle.import_application"),
                        'route' => 'mapbender_manager_application_import',
                        'enabled' => function($securityContext) {
                        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application');
                        return $securityContext->isGranted('CREATE', $oid);
                    })
                )
            ),
            array(
                'weight' => 20,
                'title' => $trans->trans("mb.manager.managerbundle.sources"),
                'route' => 'mapbender_manager_repository_index',
                'routes' => array(
                    'mapbender_manager_repository',
                ),
                'subroutes' => array(
                    array('title' => $trans->trans("mb.manager.managerbundle.add_source"),
                        'route' => 'mapbender_manager_repository_new',
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
