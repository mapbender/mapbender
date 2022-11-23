<?php

/**
 * @author Christian Wygoda
 */

namespace FOM\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $rootName = 'fom_user';
        $treeBuilder = new TreeBuilder($rootName);

        $treeBuilder->getRootNode()
            ->children()
                // not used, value irrelevant; kept to avoid errors with older
                // starter config.yml
                ->scalarNode('auto_create_log_table')
                    ->defaultTrue()
                ->end()
                ->scalarNode('login_check_log_time')
                    ->defaultValue("-5 minutes")
                ->end()
                ->scalarNode('login_attempts_before_delay')
                    ->defaultValue(3)
                ->end()
                ->scalarNode('login_delay_after_fail')
                    ->defaultValue(2)
                ->end()
                ->scalarNode('selfregister')
                    ->defaultFalse()
                ->end()
                ->scalarNode('reset_password')
                    ->defaultTrue()
                ->end()
                ->scalarNode('max_registration_time')
                    ->defaultValue(24)
                ->end()
                ->scalarNode('max_reset_time')
                    ->defaultValue(24)
                ->end()
                ->scalarNode('mail_from_address')
                    ->defaultNull()
                ->end()
                ->scalarNode('mail_from_name')
                    ->defaultNull()
                ->end()
                ->scalarNode('profile_entity')
                    ->defaultValue('FOM\UserBundle\Entity\BasicProfile')
                ->end()
                ->scalarNode('profile_formtype')
                    ->defaultValue('FOM\UserBundle\Form\Type\BasicProfileType')
                ->end()
                ->scalarNode('profile_template')
                    ->defaultValue('FOMUserBundle:User:basic_profile.html.twig')
                ->end()
                ->arrayNode('self_registration_groups')
                    ->prototype('scalar')->end()
                    ->treatNullLike(array())
                    ->defaultValue(array())
                ->end()
                ->arrayNode('user_own_permissions')
                    ->prototype('scalar')->end()
                    ->defaultValue(array('VIEW', 'EDIT'))
                ->end()
            ->end();

        return $treeBuilder;
    }
}
