<?php


namespace Mapbender\FrameworkBundle\DependencyInjection\Compiler;


use Mapbender\CoreBundle\Component\MapbenderBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterApplicationTemplatesPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /** @var string */
    protected $targetServiceId;

    public function __construct($targetServiceId = 'mapbender.application_template_registry')
    {
        $this->targetServiceId = $targetServiceId;
    }

    public function process(ContainerBuilder $container)
    {
        $priorityMap = array();
        $bundleTemplateClasses = $this->getBundleTemplates($container);
        foreach ($bundleTemplateClasses as $templateClass) {
            $priorityMap += array(0 => array());
            $priorityMap[0][] = array(
                'class' => $templateClass,
                'class_or_ref' => $templateClass,
                'tag' => array(),
            );
        }

        $tagged = $container->findTaggedServiceIds('mapbender.application_template', false);
        foreach ($tagged as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);
            $classOrRef = $definition->isAbstract() ? $definition->getClass() : new Reference($serviceId);
            foreach ($tags as $tag) {
                $priority = ($tag + array('priority' => 1))['priority'];
                $priorityMap += array($priority => array());
                $priorityMap[$priority][] = array(
                    'class' => $definition->getClass(),
                    'class_or_ref' => $classOrRef,
                    'tag' => $tag,
                );
            }
        }
        \krsort($priorityMap, SORT_NUMERIC);

        $handlers = array();
        foreach ($priorityMap as $priorityBucket) {
            foreach ($priorityBucket as $templateInfo) {
                $handlers += array($templateInfo['class'] => $templateInfo['class_or_ref']);
                if (!empty($templateInfo['tag']['replaces'])) {
                    $handlers += array($templateInfo['tag']['replaces'] => $templateInfo['class_or_ref']);
                }
            }
        }
        $targetDefinition = $container->getDefinition($this->targetServiceId);
        $targetDefinition->setArgument(0, $handlers);
    }

    /**
     * @param ContainerBuilder $container
     * @return string[]
     */
    protected function getBundleTemplates(ContainerBuilder $container)
    {
        $templateClasses = array();
        foreach ($container->getParameter('kernel.bundles') as $bundleClass) {
            if (\is_a($bundleClass, MapbenderBundle::class, true)) {
                /** @var MapbenderBundle $bundle */
                $bundle = new $bundleClass();
                $templateClasses = \array_merge($templateClasses, $bundle->getTemplates());
            }
        }
        return $templateClasses;
    }
}
