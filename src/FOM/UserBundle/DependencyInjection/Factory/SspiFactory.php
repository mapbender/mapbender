<?php

namespace FOM\UserBundle\DependencyInjection\Factory;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

class SspiFactory implements SecurityFactoryInterface
{

    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId)
    {
        $providerId = 'security.authentication.provider.sspi.'.$id;
        $container
            ->setDefinition($providerId, new ChildDefinition('sspi.security.authentication.provider'))
            ->replaceArgument(0, new Reference($userProviderId))
        ;

        $listenerId = 'security.authentication.listener.sspi.'.$id;
        $container->setDefinition($listenerId, new ChildDefinition('sspi.security.authentication.listener'));

        return array($providerId, $listenerId, $defaultEntryPointId);
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'sspi';
    }

    public function addConfiguration(NodeDefinition $builder)
    {
        //  Nothing
    }
}
