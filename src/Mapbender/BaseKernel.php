<?php

namespace Mapbender;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * Mapbender base kernel that ensures all bundles required for barebones operation are registered.
 * Optional to use. Legacy starter installations can remain blissfully unaware of this class, and continue
 * to initialize all bundles themselves.
 *
 * This is an attempt to reduce dependency coupling between Mapbender and Mapbender starter.
 * In newer project setups we expect
 * a) the actual AppKernel residing in the starter inherits from this class
 * b) it retrieves the list of bundles that we absolutely depend on for basic operation from us
 *    (by calling parent::registerBundles)
 * c) it amends that barebones list with all desired optional bundles and return the merged bundle list
 *
 * BaseKernel does not provide the bundles for backend access, printing, mobile, sending emails etc. There are
 * legitimate reasons for turning any or all of them off; also many of them require configuration to even initialize
 * properly. This configuration resides in the starter, and may have been removed on a per-project basis.
 *
 * For background @see https://github.com/mapbender/mapbender/issues/773
 *
 * As a transition helper, we provide a utility method to throw out duplicate bundle declarations.
 * @see BaseKernel::filterUniqueBundles()
 *
 * We also provide a utility to automatically discover and instantiate bundle classes from an arbitrary base namespaces,
 * which is also completely optional to use.
 * @see BaseKernel::addNameSpaceBundles()
 *
 * NOTE: do not try to extend this into the actual active kernel, getRootDir et al rely on the
 *       location of the concrete kernel class in the file system.
 */
class BaseKernel extends Kernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir() . '/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__, 3);
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', \PHP_VERSION_ID < 70400 || $this->debug);
        $container->setParameter('container.dumper.inline_factories', true);
        $confDir = $this->getProjectDir().'/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{parameters}'.self::CONFIG_EXTS, 'glob');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/'.$this->environment.'/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
    }

    public function boot(): void
    {
        parent::boot();
        if ($this->isDebug() && \class_exists('Doctrine\Deprecations\Deprecation')) {
            \Doctrine\Deprecations\Deprecation::enableWithTriggerError();
        }
    }

    protected function buildContainer(): ContainerBuilder
    {
        $container = parent::buildContainer();
        if (\class_exists('\Wheregroup\DoctrineDbalShims\DependencyInjection\Compiler\PassIndex')) {
            \Wheregroup\DoctrineDbalShims\DependencyInjection\Compiler\PassIndex::autoRegisterAll($container);
        }

        $streamEnv = \getenv('MB_LOG_STREAM');
        if ($streamEnv && $streamEnv !== 'off' && $streamEnv !== 'false') {
            if ($streamEnv !== 'stdout' && $streamEnv !== 'stderr') {
                $streamEnv = 'stdout';
            }
            $container->setParameter('log_path', "php://{$streamEnv}");
        }
        return $container;
    }
}
