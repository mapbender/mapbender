<?php

namespace Mapbender\CoreBundle\DependencyInjection\Compiler;

use Mapbender\Component\ClassUtil;
use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Component\Exception\UndefinedElementClassException;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\MapbenderCoreBundle;
use Mapbender\FrameworkBundle\Component\ElementConfigFilter;
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
class MapbenderYamlCompilerPass extends ElementConfigFilter implements CompilerPassInterface
{
    /** @var string Applications directory path where YAML files are */
    protected $applicationDir;
    /** @var boolean to throw exceptions instead of logging warnings if loaded definitions are outdated */
    protected $strictElementConfigs = false;

    /**
     * @param string|null $applicationDir Optional: explicit path to scan; default directories are configured
     */
    public function __construct($applicationDir = null)
    {
        $this->applicationDir = $applicationDir;
    }

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $sourcePaths = $container->getParameterBag()->resolveValue('%mapbender.yaml_application_dirs%');
        if ($this->applicationDir) {
            @trigger_error("DEPRECATED: explicit path passed to MapbenderYamlCompilerPass constructor. Use mapbender.yaml_application_dirs parameter collection to customize Yaml application load paths", E_USER_DEPRECATED);
            $sourcePaths = array($this->applicationDir);
        }
        $this->setStrictElementConfigs($container->getParameterBag()->resolveValue('%mapbender.strict.static_app.element_configuration%'));
        foreach ($sourcePaths as $path) {
            if (\is_dir($path)) {
                $this->loadYamlApplications($container, $path);
            }
        }
    }

    /**
     * @param bool $strict
     */
    public function setStrictElementConfigs($strict)
    {
        $this->strictElementConfigs = $strict;
    }

    /**
     * @param mixed[] $rawConfig
     * @param string $slug
     * @param string $filename
     * @return mixed[]
     */
    public function prepareApplicationConfig($rawConfig, $slug, $filename)
    {
        $configOut = $this->processApplicationDefinition($slug, $rawConfig);
        $configOut['__filename__'] = \realpath($filename);
        return $configOut;
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
                    $applications[$slug] = $this->prepareApplicationConfig($appDefinition, $slug, $file->getRealPath());
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
                    $processedDefinition = $this->processElementDefinition($elementDefinition);
                    if ($processedDefinition) {
                        $definition['elements'][$region][$elementIndex] = $processedDefinition;
                    }
                }
            }
        } else {
            unset($definition['elements']);
        }
        $layersetProps = array(
            'selected',
        );
        foreach ($definition['layersets'] as $lsIndex => $instanceConfigs) {
            foreach ($instanceConfigs as $instanceId => $instanceConfig) {
                if (\in_array($instanceId, $layersetProps)) {
                    continue;
                }
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
     * @return array|null
     */
    protected function processElementDefinition($definition)
    {
        if (empty($definition['class'])) {
            // @todo: warn? throw?
            return null;
        }

        $nonConfigKeys = $this->getTopLevelElementKeys();
        $element = new Element();
        $nonConfigs = array_intersect_key($definition, array_flip($nonConfigKeys));
        $configBefore = array_diff_key($definition, $nonConfigs);
        $element->setClass($definition['class']);
        $element->setConfiguration($configBefore);

        $handlingClass = $this->getHandlingClassName($element);
        if (!$handlingClass) {
            if ($this->strictElementConfigs) {
                throw new \RuntimeException('Missing reuired Yaml Element definition value for "class"');
            }
            // @todo: warn?
            return null;
        } elseif (!ClassUtil::exists($handlingClass)) {
            $msg = "Your Yaml application contains an undefined / unhandled element class {$definition['class']}";
            @trigger_error("WARNING: {$msg}", E_USER_DEPRECATED);
        }

        $definition['class'] = $handlingClass;

        try {
            $this->migrateConfigInternal($element, $handlingClass);
            $configAfter = $element->getConfiguration();
            $this->onElementConfigChange($definition['class'], $configBefore, $configAfter);
            $definition = array_replace($configAfter, $nonConfigs);
            $this->checkElementConfig($handlingClass, array_diff_key($definition, array_flip($nonConfigKeys)));
        } catch (UndefinedElementClassException $e) {
            // May be a canoncial. Keep the Element without migrating.
        }

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
        // Do not warn for added defaults
        if ($addedKeys && $removedKeys) {
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

    /**
     * @return string[]
     */
    protected function getTopLevelElementKeys()
    {
        return array(
            'title',
            'roles',
            'class',
            'screenType',
        );
    }
}
