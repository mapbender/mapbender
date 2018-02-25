<?php
namespace Mapbender;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Mapbender base kernel that ensures all bundles required for barebones operation are registered.
 *
 * This is the dependency-decoupling bridge between Mapbender and Mapbender starter.
 * We expect this class to be inherited from by the actual AppKernel residing in the starter,
 * which should add all the remaining, truly optional and project-specific bundles. I.e. we do
 * not provide the bundles for backend access, printing, mobile, sending emails etc. There are
 * legitimate reasons for turning any or all of them off.
 *
 * We do however define here the bundles that we absolutely depend on for basic operation.
 *
 * @see https://github.com/mapbender/mapbender/issues/773
 *
 * NOTE: do not try to extend this into the actual active kernel, getRootDir et al rely on the
 *       location of the concrete kernel class in the file system.
 */
abstract class BaseKernel extends Kernel
{
    /**
     * Search and initialize name space bundles.
     * Search approach uses indirectly the composer auto generated file to get bundle names.
     *
     * This was originally introduced in a Mapbender 3.0.6 starter change
     * @see https://github.com/mapbender/mapbender-starter/compare/8028d4ec86a9f060a2654e0f4b5443931493ae13...83a751528c309836130102b6ab6b07f76c74b932#diff-9166876875e5d5b4c5846613d76634e8
     *
     * Since this method doesn't take effect unless called explicitly from the active AppKernel, we can introduce
     * it safely into earlier versions, which will ease the transition.
     *
     * @param BundleInterface[] $bundles   Bundle array link
     * @param string            $nameSpace Name space prefix as string
     * @return BundleInterface[] Bundle array
     */
    public function addNameSpaceBundles(array &$bundles, $nameSpace)
    {
        $vendorRoot = realpath($this->getRootDir() . '/../vendor');

        $namespaces = include("{$vendorRoot}/composer/autoload_namespaces.php");
        foreach ($namespaces as $name => $path) {
            if (strpos($name, $nameSpace) === 0) {
                $bundleClassName = $name . '\\' . str_replace('\\', "", $name);
                $bundles[] = new $bundleClassName();
            }

        }

        $namespaces = include("{$vendorRoot}/composer/autoload_psr4.php");
        foreach ($namespaces as $name => $path) {
            if (strpos($name, $nameSpace) === 0
                && strpos($name, "Bundle")
            ) {
                $bundleClassName = $name . str_replace('\\', "", $name);
                $bundles[] = new $bundleClassName();
            }
        }
        return $bundles;
    }

    /**
     * @return array|BundleInterface[]
     */
    public function registerBundles()
    {
        $bundles = array(
            // Standard Symfony2 bundles
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            // Bare-bones Mapbender
            new CoreBundle\MapbenderCoreBundle(),
        );

        // dev and ALL test environments get some extra sugar...
        $isDevKernel = false;
        if('dev' == $this->getEnvironment() || strpos($this->getEnvironment(), 'test') == 0) {
            $isDevKernel = true;
        }

        if ($isDevKernel) {
            $bundles[] = new \Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new \Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new \Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    /**
     * Helper method to remove repeated bundles, retaining bundle order. Symfony initialization will throw
     * an error "Uncaught LogicException: Trying to register two bundles with the same name" if the same bundle
     * class appears twice.
     *
     * You SHOULD make sure to add each bundle only once, but if you're having difficulties you can use this
     * to filter out dupes.
     *
     * The bundle constructor is pretty light, so instantiating too many is not a big performance problem.
     *
     * The real problem we solve here is that the "registerBundles" return type is not a list of class names,
     * but a list of already created instances.
     *
     * @param BundleInterface[] $bundles
     * @return BundleInterface[] input filtered down to only unique classes
     */
    public static function filterUniqueBundles($bundles)
    {
        // force contiguous numeric ids (array_map discards keys)
        $bundles = array_values($bundles);

        $bundleClasses = array_map('get_class', $bundles);
        // array_unique preserves keys, keeps only first occurence of class name
        $keptBundleClasses = array_unique($bundleClasses);
        // intersect instances with deduped keys => instances of same class gone, order preserved
        $keptBundleInstances = array_intersect_key($bundles, $keptBundleClasses);
        return $keptBundleInstances;
    }
}
