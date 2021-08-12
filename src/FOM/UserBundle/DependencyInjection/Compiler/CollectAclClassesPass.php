<?php


namespace FOM\UserBundle\DependencyInjection\Compiler;


use Mapbender\ManagerBundle\Component\ManagerBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Pass collecting globally assignable Acl classes and placing them
 * in container parameter `fom.user.acl_classes`
 *
 * @see ManagerBundle::getACLClasses()
 */
class CollectAclClassesPass implements CompilerPassInterface
{
    protected $parameterName;

    public function __construct($parameterName)
    {
        $this->parameterName = $parameterName;
    }

    public function process(ContainerBuilder $container)
    {
        $bundleClasses = \array_values($container->getParameterBag()->resolveValue('%kernel.bundles%'));
        $classMap = array();
        foreach ($bundleClasses as $bundleFqcn) {
            if (\is_a($bundleFqcn, 'Mapbender\ManagerBundle\Component\ManagerBundle', true)) {
                /** @var ManagerBundle $bundle */
                $bundle = new $bundleFqcn();
                $classMap += $bundle->getACLClasses();
            }
        }
        $merged = $container->getParameterBag()->resolveValue('%' . $this->parameterName . '%') ?: array();
        $merged = $classMap + $merged;
        $container->setParameter($this->parameterName, $merged);
    }
}
