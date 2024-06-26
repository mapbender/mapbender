<?php


namespace Mapbender\WmtsBundle\DependencyInjection\Compiler;


use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterWmtsSourceServicePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Register wms config generation service with source type directory service in CoreBundle.
        // This is done here because service definitions cannot be amended via XML / YAML across bundles.
        $typeDirectoryDefinition = $container->getDefinition('mapbender.source.typedirectory.service');
        /** @see TypeDirectoryService::registerSubtypeService */
        $typeDirectoryDefinition->addMethodCall('registerSubtypeService', array(
            'wmts',
            new Reference('mapbender.source.wmts.config_generator'),
            new Reference('mapbender.source.wmts.instance_factory'),
            new Reference('mapbender.importer.source.wmts.service'),
        ));
        $typeDirectoryDefinition->addMethodCall('registerSubtypeService', array(
            'tms',
            new Reference('mapbender.source.tms.config_generator'),
            new Reference('mapbender.source.tms.instance_factory'),
            new Reference('mapbender.importer.source.wmts.service'),
        ));
    }
}
