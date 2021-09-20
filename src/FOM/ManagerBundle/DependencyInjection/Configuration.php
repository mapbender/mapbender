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
    public function getConfigTreeBuilder()
    {
        $rootName = 'fom_manager';
        $treeBuilder = new TreeBuilder($rootName);
        if (\method_exists($treeBuilder, 'getRootNode')) {
            // Symfony >= 4.1+
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // Deprecated on Symfony 4, error on Symfony 5
            $rootNode = $treeBuilder->root($rootName);
        }

        $rootNode
            ->children()
                ->scalarNode('route_prefix')
                    ->defaultValue('manager')
                ->end()
            ->end();

        return $treeBuilder;
    }
}

