<?php

namespace Mapbender\CoreBundle\DependencyInjection\Compiler;

use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\MapbenderCoreBundle;
use Symfony\Component\Config\Resource\DirectoryResource;
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
    /** @var boolean to throw exceptions instead of logging warnings if loaded definitions are outdated */
    protected $strictElementConfigs = false;

    /**
     * MapbenderYamlCompilerPass constructor.
     *
     * @param string             $applicationDir       Applications directory path
     */
    public function __construct($applicationDir)
    {
        $this->applicationDir = $applicationDir;
    }

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->strictElementConfigs = $this->resolveParameterReference($container, 'mapbender.strict.static_app.element_configuration');
        if ($this->applicationDir) {
            $this->loadYamlApplications($container, $this->applicationDir);
        }
    }

    protected function resolveParameterReference(ContainerBuilder $container, $name)
    {
        $value = $container->getParameter($name);
        while (preg_match('/^[%].*?[%]$/', $value)) {
            $value = $container->getParameter(substr($value, 1, -1));
        }
        return $value;
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
            $fileData = Yaml::parse(file_get_contents($file->getRealPath()));
            if (!empty($fileData['parameters']['applications'])) {
                foreach ($fileData['parameters']['applications'] as $slug => $appDefinition) {
                    $applications[$slug] = $this->processApplicationDefinition($slug, $appDefinition);
                    $applications[$slug]['__filename__'] = $file->getRealPath();
                }
            }
        }
        $container->addResource(new DirectoryResource($path));
        $this->addApplications($container, $applications);
    }

    /**
     * @param ContainerBuilder $container
     * @param array[][] $applications
     */
    protected function addApplications($container, $applications)
    {
        if ($applications) {
            $applicationCollection = $container->getParameter('applications');
            $applicationCollection = array_replace($applicationCollection, $applications);
            $container->setParameter('applications', $applicationCollection);
        }
    }

    /**
     * @param string $slug
     * @param array $definition
     * @return array
     */
    protected function processApplicationDefinition($slug, $definition)
    {
        if (!isset($definition['layersets'])) {
            if (isset($definition['layerset'])) {
                // @todo: add strict mode support and throw if enabled
                @trigger_error("Deprecated: your YAML application {$slug} defines legacy 'layerset' (single item), should define 'layersets' (array)", E_USER_DEPRECATED);
                $definition['layersets'] = array($definition['layerset']);
            } else {
                $definition['layersets'] = array();
            }
        }
        unset($definition['layerset']);
        if (!empty($definition['elements'])) {
            foreach ($definition['elements'] as $region => $elementDefinitionList) {
                foreach ($elementDefinitionList as $elementIndex => $elementDefinition) {
                    $definition['elements'][$region][$elementIndex] = $this->processElementDefinition($elementDefinition);
                }
            }
        } else {
            unset($definition['elements']);
        }
        foreach ($definition['layersets'] as $lsIndex => $instanceConfigs) {
            foreach ($instanceConfigs as $instanceId => $instanceConfig) {
                $definition['layersets'][$lsIndex][$instanceId] = $this->processSourceInstanceDefinition($instanceConfig, $instanceId, $lsIndex);
            }
        }
        if (isset($definition['published'])) {
            // force to boolean
            $definition['published'] = !!$definition['published'];
        } else {
            // strip null value
            unset($definition['published']);
        }
        return $definition;
    }

    /**
     * @param array $definition
     * @return array
     */
    protected function processElementDefinition($definition)
    {
        // @todo: look up and adjust migrated class names as well
        if (\is_a($definition['class'], 'Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface', true)) {
            /** @var string|\Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface $className */
            $className = $definition['class'];
            $dummyEntity = new Element();
            $nonConfigs = array_intersect_key($definition, array(
                'class' => true,
                'title' => true,
            ));
            $configBefore = array_diff_key($definition, $nonConfigs);
            $dummyEntity->setConfiguration($configBefore);
            $className::updateEntityConfig($dummyEntity);
            $configAfter = $dummyEntity->getConfiguration();
            $this->onElementConfigChange($nonConfigs['class'], $configBefore, $configAfter);
            $definition = array_replace($configAfter, $nonConfigs);
        }
        $this->checkElementConfig($definition['class'], array_diff_key($definition, array_flip(array(
            'class',
            'title',
        ))));
        return $definition;
    }

    /**
     * Invoked when an element configuration needed adjustment.
     * May either log a warning message or throw, depending on configuration parameter
     * 'mapbender.strict.static_app.element_configuration'.
     *
     * @param $className
     * @param $configBefore
     * @param $configAfter
     */
    protected function onElementConfigChange($className, $configBefore, $configAfter)
    {
        $changedValueKeys = array_keys(array_uintersect_assoc($configAfter, $configBefore, function ($a, $b) {
            return ($a !== $b) ? 0 : (-1 + 2 * intval($a > $b));
        }));
        $keysBefore = array_keys($configBefore);
        $keysAfter = array_keys($configAfter);
        $removedKeys = array_diff($keysBefore, $keysAfter);
        $addedKeys = array_diff($keysAfter, $keysBefore);
        $messageParts = array();
        if ($removedKeys) {
            $messageParts[] = 'removed ' . implode(', ', $removedKeys);
        }
        if ($addedKeys) {
            $messageParts[] = 'added ' . implode(', ', $addedKeys);
        }
        foreach ($changedValueKeys as $k) {
            $messageParts[] = implode(' ', array(
                'changed',
                $k,
                'from',
                var_export($configBefore[$k], true),
                'to',
                var_export($configAfter[$k], true),
            ));
        }
        if ($messageParts) {
            $messageCommon = "outdated {$className} configuration: " . implode('; ', $messageParts);
            if ($this->strictElementConfigs) {
                throw new \RuntimeException("Yaml application contains {$messageCommon}");
            } else {
                @trigger_error("WARNING: had to perform adjustments on {$messageCommon}. Update your Yaml application definition accordingly");
            }
        }
    }

    /**
     * Inspect Element configuration for values not present in Element component's default configuration.
     * May either log a warning message or throw, depending on configuration parameter
     * 'mapbender.strict.static_app.element_configuration'.
     *
     * @param string|MinimalInterface $className
     * @param $config
     */
    protected function checkElementConfig($className, $config)
    {
        $defaults = $className::getDefaultConfiguration();
        $keysWithoutDefaults = array_diff(array_keys($config), array_keys($defaults));
        if ($keysWithoutDefaults) {
            $messageCommon = "configuration for {$className} with invalid keys " . implode(',', $keysWithoutDefaults);
            if ($this->strictElementConfigs) {
                throw new \RuntimeException("Yaml application contains {$messageCommon}");
            } else {
                @trigger_error("WARNING: had to perform adjustments on {$messageCommon}. Update your Yaml application definition accordingly");
            }
        }
    }

    /**
     * @param array $definition
     * @param string $instanceId
     * @param mixed $lsIndex
     * @return array
     */
    protected function processSourceInstanceDefinition($definition, $instanceId, $lsIndex)
    {
        if (empty($definition['type']) && !empty($definition['class'])) {
            if (is_a($definition['class'], 'Mapbender\WmsBundle\Entity\WmsInstance', true)) {
                $definition['type'] = Source::TYPE_WMS;
            } else {
                // NOTE: WmtsInstance is actually already two types, WMTS and TMS
                throw new \RuntimeException("Can't infer type from instance class name {$definition['class']})");
            }
        }
        if (empty($definition['type'])) {
            throw new \RuntimeException("Missing instance type in yaml application layerset {$lsIndex}, instance id {$instanceId}");
        }
        unset($definition['class']);
        return $definition;
    }
}
