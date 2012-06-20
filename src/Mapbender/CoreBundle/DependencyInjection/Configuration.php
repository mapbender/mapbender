<?php

/**
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */

namespace Mapbender\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('mapbender_core');

        $defaultScreenshotPath = 'app_screenshots';

        $rootNode
            ->children()
                ->scalarNode('screenshot_path')
                    ->defaultValue($defaultScreenshotPath)
                ->end()
                ->arrayNode('proxy')
                    ->canBeUnset()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')
                            ->defaultNull()
                            ->end()
                        ->scalarNode('port')
                            ->defaultNull()
                            ->end()
                        ->scalarNode('user')
                            ->defaultNull()
                            ->end()
                        ->scalarNode('password')
                            ->defaultNull()
                            ->end()
                        ->arrayNode('noproxy')
                            ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}

