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

    	$defaultUploadDir = "uploads"; # from application/web

        $rootNode
            ->children()
	        ->scalarNode('uploads_dir')
                    ->defaultValue($defaultUploadDir)
                ->end()
            ->end();

        return $treeBuilder;
    }
}

