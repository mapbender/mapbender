<?php


namespace Mapbender\WmtsBundle\DependencyInjection\Compiler;


use Mapbender\PrintBundle\Component\ImageExportService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterWmtsExportLayerRendererPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $wmtsRendererId = 'mapbender.imageexport.renderer.wmts';
        $tmsRendererId = 'mapbender.imageexport.renderer.tms';
        $imageExportServiceDefinition = $container->getDefinition('mapbender.imageexport.service');
        $printServiceDefinition = $container->getDefinition('mapbender.print.service');
        /** @see ImageExportService::addLayerRenderer() */
        $imageExportServiceDefinition->addMethodCall('addLayerRenderer', array(
            'wmts',
            new Reference($wmtsRendererId),
        ));
        $imageExportServiceDefinition->addMethodCall('addLayerRenderer', array(
            'tms',
            new Reference($tmsRendererId),
        ));
        $printServiceDefinition->addMethodCall('addLayerRenderer', array(
            'wmts',
            new Reference($wmtsRendererId),
        ));
        $printServiceDefinition->addMethodCall('addLayerRenderer', array(
            'tms',
            new Reference($tmsRendererId),
        ));
    }
}
