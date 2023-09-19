<?php

namespace Mapbender;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * Mapbender base kernel
 */
class BaseKernel extends Kernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /** @noinspection PhpUnused used when creating kernel, necessary to include parameters.yml file */
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
