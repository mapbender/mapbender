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
	$defaultWmcDir = "uploads/wmc"; # from application/web

        $rootNode
            ->children()
	        ->scalarNode('wmc_dir')
                    ->defaultValue($defaultWmcDir)
                ->end()
                ->scalarNode('selfregister')
                    ->defaultFalse()
                ->end()
                ->scalarNode('max_registration_time')
                    ->defaultValue(24)
                ->end()
                ->scalarNode('max_reset_time')
                    ->defaultValue(24)
                ->end()
                ->scalarNode('screenshot_path')
                    ->defaultValue($defaultScreenshotPath)
                ->end()
            ->end();

        return $treeBuilder;
    }
}

