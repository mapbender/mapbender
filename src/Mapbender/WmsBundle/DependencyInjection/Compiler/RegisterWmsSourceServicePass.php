<?php


namespace Mapbender\WmsBundle\DependencyInjection\Compiler;

use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterWmsSourceServicePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // Register wms config generation service with source type directory service in CoreBundle.
        // This is done here because service definitions cannot be amended via XML / YAML across bundles.
        $typeDirectoryDefinition = $container->getDefinition('mapbender.source.typedirectory.service');
        /** @see TypeDirectoryService::registerSubtypeService */
        $typeDirectoryDefinition->addMethodCall('registerSubtypeService', array(
            'wms',
            'mapbender.source.wms.service',
        ));
    }
}
