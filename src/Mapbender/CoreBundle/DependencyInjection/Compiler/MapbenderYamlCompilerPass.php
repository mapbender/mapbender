<?php

namespace Mapbender\CoreBundle\DependencyInjection\Compiler;

use Mapbender\CoreBundle\MapbenderCoreBundle;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Class MapbenderYamlCompilerPass
 *
 * Need to load and create bundle application cache.
 * @see MapbenderCoreBundle::build()
 *
 * @package Mapbender\CoreBundle\DependencyInjection\Compiler
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class MapbenderYamlCompilerPass implements CompilerPassInterface
{
    /** @var ContainerBuilder|ContainerInterface Container */
    protected $container;

    /** @var string Application YAML file path */
    protected $applicationsFilePath;

    /** @var string Applications directory path where YAML files are */
    protected $applicationDir;

    /**
     * MapbenderYamlCompilerPass constructor.
     *
     * @param string             $applicationDir       Applications directory path
     * @param bool               $applicationsFilePath Application YAML file path
     */
    public function __construct(
        $applicationDir = null,
        $applicationsFilePath = null)
    {

        if ($applicationDir) {
            $this->applicationDir = $applicationDir;
        }

        if ($applicationsFilePath) {
            $this->applicationsFilePath = $applicationsFilePath;
        }
    }

    /**
     * @param ContainerBuilder|ContainerInterface $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @param ContainerBuilder $container Container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container) {
            $this->setContainer($container);
        }

        if ($this->applicationsFilePath) {
            $this->loadAndMergeYamlParameters($this->applicationsFilePath);
        }
        if ($this->applicationDir) {
            $this->loadYamlApplications($this->applicationDir);
        }
    }

    /**
     * Load YAML applications from path
     *
     * @param string $path Application directory path
     */
    public function loadYamlApplications($path)
    {
        $container = $this->container;
        $finder    = new Finder();
        $finder
            ->in($path)
            ->files()
            ->name('*.yml');

        foreach ($finder as $file) {
            $this->loadAndMergeYamlParameters($file->getRealPath());
        }

        $container->getParameterBag()->resolve();
    }

    /**
     * Load and merge d YAML file parameters data
     *
     * @param string $filePath Absolute file path
     * @return FileResource
     */
    public function loadAndMergeYamlParameters($filePath)
    {
        $container    = $this->container;
        $fileResource = new FileResource($filePath);
        $yml          = Yaml::parse($filePath);

        $container->addResource($fileResource);

        if (array_key_exists('parameters', $yml) && is_array($yml['parameters'])) {
            foreach ($yml['parameters'] as $key => $data) {
                $this->mergeParameterData($key, $data);
            }
        }

        return $fileResource;
    }


    /**
     * Merge container parameter data
     *
     * @param $key
     * @param $data
     */
    public function mergeParameterData($key, &$data)
    {
        $container = $this->container;
        if ($container->hasParameter($key)) {
            $container->setParameter($key,
                array_merge_recursive(
                    $container->getParameter($key),
                    $data
                )
            );
        } else {
            $container->setParameter($key, $data);
        }
    }
}
