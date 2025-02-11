<?php


namespace Mapbender\FrameworkBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterGlobalPermissionDomainsPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function __construct(protected string $indexId)
    {
    }

    public function process(ContainerBuilder $container): void
    {
        $globalPermissions = $this->findAndSortTaggedServices('fom.security.global_permission', $container);
        $container->getDefinition($this->indexId)
            ->replaceArgument(0, $globalPermissions)
        ;
    }
}
