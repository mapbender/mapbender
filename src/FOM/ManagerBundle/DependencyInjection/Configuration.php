<?php

/**
 * @author Christian Wygoda
 */

namespace FOM\ManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle configuration definition
 *
 * @author Christian Wygoda
 */
class Configuration implements ConfigurationInterface {
    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fom_manager');

        $rootNode
            ->children()
                ->scalarNode('route_prefix')
                    ->defaultValue('manager')
                ->end()
            ->end();

        return $treeBuilder;
    }
}

