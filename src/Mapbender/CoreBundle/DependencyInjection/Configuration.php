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
    	$defaultUploadDir = "uploads"; # from application/web

        $rootNode
            ->children()
	        ->scalarNode('uploads_dir')
                    ->defaultValue($defaultUploadDir)
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
                ->booleanNode('static_assets')
                    ->defaultTrue()
                ->end()
                ->scalarNode('static_assets_cache_path')
                    ->defaultValue('%kernel.root_dir%/../web/assets')
                ->end()
            ->end();

        return $treeBuilder;
    }
}

