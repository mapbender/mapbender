<?php

namespace FOM\ManagerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Bundle container extension class
 *
 * @author Christian Wygoda
 */
class FOMManagerExtension extends Extension
{
    /**
     * @inheritdoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('fom_manager.route_prefix', $config['route_prefix']);
    }

    /**
     * @inheritdoc
     */
    public function getAlias()
    {
        return 'fom_manager';
    }
}

