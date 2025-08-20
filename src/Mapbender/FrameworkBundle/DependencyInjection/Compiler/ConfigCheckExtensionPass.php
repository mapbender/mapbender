<?php


namespace Mapbender\FrameworkBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigCheckExtensionPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function __construct(protected string $indexId)
    {
    }

    public function process(ContainerBuilder $container): void
    {
        $configCheckExtensions = $this->findAndSortTaggedServices('mapbender.config_check', $container);
        $container->getDefinition($this->indexId)
            ->replaceArgument(0, $configCheckExtensions)
        ;
    }
}
