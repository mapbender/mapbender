<?php

/**
 * @author Christian Wygoda
 */

namespace FOM\UserBundle\DependencyInjection;

use FOM\UserBundle\Entity\BasicProfile;
use FOM\UserBundle\Form\Type\BasicProfileType;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $rootName = 'fom_user';
        $treeBuilder = new TreeBuilder($rootName);

        $treeBuilder->getRootNode()
            ->children()
                // not used, value irrelevant; kept to avoid errors with older
                // starter config.yaml
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
                    ->defaultValue(BasicProfile::class)
                ->end()
                ->scalarNode('profile_formtype')
                    ->defaultValue(BasicProfileType::class)
                ->end()
                ->scalarNode('profile_template')
                    ->defaultValue('@FOMUser/User/basic_profile.html.twig')
                ->end()
                ->arrayNode('self_registration_groups')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                    ->defaultValue([])
                ->end()
                ->arrayNode('user_own_permissions')
                    ->prototype('scalar')->end()
                    ->defaultValue(['VIEW', 'EDIT'])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
