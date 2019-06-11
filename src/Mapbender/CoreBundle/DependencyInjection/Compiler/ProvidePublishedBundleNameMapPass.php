<?php


namespace Mapbender\CoreBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class ProvidePublishedBundleNameMapPass implements CompilerPassInterface
{
    /** @var KernelInterface */
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function process(ContainerBuilder $container)
    {
        $bundlePathMap = $this->getBundleNameMap($this->kernel);
        $container->setParameter('mapbender.published_bundle_name_map', $bundlePathMap);
    }

    /**
     * Return a mapping of published bundle path fragments ('bundlename' under <webroot>/bundles) to original
     * bundle name ('BundleNameBundle')
     *
     * @param KernelInterface $kernel
     * @return string[]
     */
    public static function getBundleNameMap(KernelInterface $kernel)
    {
        $nameMap = array();
        foreach ($kernel->getBundles() as $bundle) {
            $bundleName = $bundle->getName();
            $publishedPath = 'bundles/' . strtolower(preg_replace('#Bundle$#', '', $bundleName));
            $nameMap[$publishedPath] = $bundleName;
        }
        return $nameMap;
    }
}
