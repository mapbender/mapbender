<?php

namespace FOM\UserBundle\DependencyInjection\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SspiFactory implements AuthenticatorFactoryInterface
{

    public function addConfiguration(NodeDefinition $builder)
    {
        //  Nothing
    }

    //     public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId)

    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string|array
    {
        $providerId = 'security.authentication.provider.sspi.'.$firewallName;
        $container
            ->setDefinition($providerId, new ChildDefinition('sspi.security.authentication.provider'))
            ->replaceArgument(0, new Reference($userProviderId))
        ;

        $listenerId = 'security.authentication.listener.sspi.'.$firewallName;
        $container->setDefinition($listenerId, new ChildDefinition('sspi.security.authentication.listener'));

        return [$providerId, $listenerId];
    }

    public function getPriority(): int
    {
        return -10; // pre_auth, see Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension
    }

    public function getKey(): string
    {
        return 'sspi';
    }

}
