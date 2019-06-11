<?php

namespace Mapbender\CoreBundle\DependencyInjection\Compiler;

use Mapbender\CoreBundle\MapbenderCoreBundle;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Class MapbenderYamlCompilerPass
 *
 * Need to load and create bundle application cache.
 * @see MapbenderCoreBundle::build()
 *
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class MapbenderYamlCompilerPass implements CompilerPassInterface
{
    /** @var string Applications directory path where YAML files are */
    protected $applicationDir;

    /**
     * MapbenderYamlCompilerPass constructor.
     *
     * @param string             $applicationDir       Applications directory path
     */
    public function __construct($applicationDir)
    {
        if ($applicationDir) {
            $this->applicationDir = $applicationDir;
        }
    }

    /**
     * @param ContainerBuilder $container Container
     */
    public function process(ContainerBuilder $container)
    {
        if ($this->applicationDir) {
            $this->loadYamlApplications($container, $this->applicationDir);
        }
    }

    /**
     * Load YAML applications from path
     *
     *
     * @param ContainerBuilder $container
     * @param string $path Application directory path
     */
    protected function loadYamlApplications($container, $path)
    {
        $finder = new Finder();
        $finder
            ->in($path)
            ->files()
            ->name('*.yml');
        $applications = array();

        foreach ($finder as $file) {
            $fileData = Yaml::parse($file->getRealPath());
            if (!empty($fileData['parameters']['applications'])) {
                $applications = array_replace($applications, $fileData['parameters']['applications']);
                // Add a file resource to auto-invalidate the container build when the input file changes
                $container->addResource(new FileResource($file->getRealPath()));
            }
        }
        $this->addApplications($container, $applications);
    }

    /**
     * @param ContainerBuilder $container
     * @param array[][] $applications
     */
    protected function addApplications($container, $applications)
    {
        if ($applications) {
            if ($container->hasParameter('applications')) {
                $applicationCollection = $container->getParameter('applications');
                $applicationCollection = array_replace($applicationCollection, $applications);
            } else {
                $applicationCollection = $applications;
            }
            $container->setParameter('applications', $applicationCollection);
        }
    }
}
