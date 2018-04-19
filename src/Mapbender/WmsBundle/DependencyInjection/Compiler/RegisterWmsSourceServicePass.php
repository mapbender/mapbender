<?php


namespace Mapbender\WmsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterWmsSourceServicePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // Register wms config generation service with config service in CoreBundle.
        // This is done here because service definitions cannot be amended via XML / YAML across bundles.
        $appConfigServiceDef = $container->getDefinition('mapbender.presenter.application.config.service');
        /** @see ConfigService::addSourceService */
        $appConfigServiceDef->addMethodCall('addSourceService', array(
            'wms',
            'mapbender.presenter.source.wms.service',
        ));
    }
}
