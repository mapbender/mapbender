<?php


namespace Mapbender\FrameworkBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterIconPackagesPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /** @var string */
    protected $indexId;

    public function __construct($indexId)
    {
        $this->indexId = $indexId;
    }

    public function process(ContainerBuilder $container)
    {
        $packages = $this->findAndSortTaggedServices('mapbender.icon_package', $container);
        $container->getDefinition($this->indexId)->replaceArgument(0, $packages);
    }
}
