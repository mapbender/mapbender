<?php

/**
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */

namespace Mapbender\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @deprecated remove in v3.1
 */
class Configuration implements ConfigurationInterface {
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('mapbender_core');
        $rootNode
            ->children()
	        ->scalarNode('uploads_dir')
                    ->defaultFalse()
                ->end()
            ->end();

        return $treeBuilder;
    }
}

