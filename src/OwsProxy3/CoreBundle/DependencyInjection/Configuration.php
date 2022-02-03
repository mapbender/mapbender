<?php

namespace OwsProxy3\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Christian Wygoda
 */
class Configuration implements ConfigurationInterface
{

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $rootName = 'ows_proxy3_core';
        $treeBuilder = new TreeBuilder($rootName);
        if (\method_exists($treeBuilder, 'getRootNode')) {
            // Symfony >= 4.2
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // Deprecated on Symfony 4, error on Symfony 5
            $rootNode = $treeBuilder->root($rootName);
        }

        $rootNode
            ->canBeUnset()->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('logging')
                    ->defaultFalse()
                    ->treatNullLike(false)
                ->end()
                ->booleanNode('obfuscate_client_ip')
                    ->defaultTrue()
                    ->treatNullLike(true)
                ->end()
                ->arrayNode("proxy")->canBeUnset()->addDefaultsIfNotSet()->children()
                    ->scalarNode('host')->defaultNull()->end()
                    ->scalarNode('port')->defaultNull()->end()
                    ->scalarNode('connecttimeout')
                        ->defaultValue(30)
                        ->treatNullLike(30)
                    ->end()
                    ->scalarNode('timeout')
                        ->defaultValue(60)
                        ->treatNullLike(60)
                    ->end()
                    ->scalarNode('user')->defaultNull()->end()
                    ->scalarNode('password')->defaultNull()->end()
                    ->scalarNode('checkssl')
                        ->defaultValue(true)
                        ->treatNullLike(true)
                    ->end()
                    ->arrayNode("noproxy")
                        ->prototype('scalar')
                    ->end()
                    ->end()
                ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }

}
