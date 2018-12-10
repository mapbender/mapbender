<?php

namespace Mapbender\PrintBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AddBasePrintPluginsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $printBridgeDefinition = $container->getDefinition('mapbender.print_service_bridge.service');
        $this->tryAddDigitizerPlugin($container, $printBridgeDefinition);
    }

    protected function tryAddDigitizerPlugin(ContainerBuilder $container, Definition $bridgeDefinition)
    {
        // Only add the digitizer plugin if its 'featureTypesParamName' references a parameter present in the container
        // and non-empty (i.e. null / empty array is treated the same as no parameter definition at all)
        $digitizerPluginId = 'mapbender.print.plugin.digitizer';
        $digitizerPluginDefinition = $container->getDefinition($digitizerPluginId);
        $ftParamName0 = $digitizerPluginDefinition->getArgument('featureTypeParamName');
        $ftParamName = $this->resolveParameterReference($container, $ftParamName0);
        if ($container->hasParameter($ftParamName) && $container->getParameter($ftParamName)) {
            $bridgeDefinition->addMethodCall('registerPlugin', array($digitizerPluginId));
        }
    }

    /**
     * Get a concrete parameter value from an unresolved '%something%'-style parameter reference that occurs
     * commonly in the compilation phase of the container lifecycle.
     *
     * @param ContainerBuilder $container
     * @param string $value
     * @return mixed
     */
    protected function resolveParameterReference(ContainerBuilder $container, $value)
    {
        if (is_string($value)) {
            while (preg_match('#^%[^%]+%$#', $value)) {
                $value = $container->getParameter(trim($value, '%'));
            }
            return $value;
        } else {
            throw new \InvalidArgumentException("Unhandled type " . gettype($value));
        }
    }
}
