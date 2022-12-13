<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\Component\Collections\YamlElementCollection;
use Mapbender\Component\Collections\YamlSourceInstanceCollection;
use Mapbender\Component\SourceInstanceFactory;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\FrameworkBundle\Component\ElementEntityFactory;
use Mapbender\FrameworkBundle\Listener\ApplicationEngineListener;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Converts array-style application definitions to Application entities.
 *
 * Service instance registered as mapbender.application.yaml_entity_repository
 * @todo: implement object repository interface
 * @todo: split factory from repository
 *
 * @author Christian Wygoda
 */
class ApplicationYAMLMapper
{
    /** @var LoggerInterface  */
    protected $logger;
    /** @var TypeDirectoryService */
    protected $sourceTypeDirectory;
    /** @var ElementEntityFactory */
    protected $elementFactory;
    /** @var ApplicationEngineListener */
    protected $onLoadListener;
    /** @var array[] */
    protected $definitions;

    /**
     * @param array[] $definitions
     * @param ElementEntityFactory $elementFactory
     * @param SourceInstanceFactory $sourceInstanceFactory
     * @param ApplicationEngineListener $onLoadListener
     * @param LoggerInterface|null $logger
     */
    public function __construct($definitions,
                                ElementEntityFactory $elementFactory, SourceInstanceFactory $sourceInstanceFactory,
                                ApplicationEngineListener $onLoadListener,
                                LoggerInterface $logger = null)
    {
        $this->definitions = $definitions;
        $this->elementFactory = $elementFactory;
        $this->sourceTypeDirectory = $sourceInstanceFactory;
        $this->onLoadListener = $onLoadListener;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Get all YAML applications
     *
     * @return Application[]
     */
    public function getApplications()
    {
        $applications = array();
        foreach ($this->definitions as $slug => $def) {
            $application = $this->getApplication($slug);
            if ($application !== null) {
                $applications[] = $application;
            }
        }

        return $applications;
    }

    /**
     * Get YAML application for given slug
     *
     * Will return null if no YAML application for the given slug exists.
     *
     * @param string $slug
     * @return Application|null
     */
    public function getApplication($slug)
    {
        if (!array_key_exists($slug, $this->definitions)) {
            return null;
        }
        $application = $this->createApplication($this->definitions[$slug], $slug);
        return $application;
    }

    /**
     * @param mixed[] $definition
     * @param string $slug
     * @return Application
     */
    public function createApplication(array $definition, $slug)
    {

        $timestamp = filemtime($definition['__filename__']);
        unset($definition['__filename__']);
        if (!array_key_exists('title', $definition)) {
            $definition['title'] = "TITLE " . $timestamp;
        }

        $application = new Application();
        $application->setId($slug);
        $application->setSlug($slug);
        $application->setUpdated(new \DateTime("@{$timestamp}"));
        $application
                ->setTitle(isset($definition['title'])?$definition['title']:'')
                ->setDescription(isset($definition['description'])?$definition['description']:'')
                ->setTemplate($definition['template'])
        ;
        if (isset($definition['published'])) {
            $application->setPublished($definition['published']);
        }
        if (!empty($definition['screenshot'])) {
            $application->setScreenshot($definition['screenshot']);
        }
        if (isset($definition['custom_css'])) {
            $application->setCustomCss($definition['custom_css']);
        }

        if (isset($definition['publicOptions'])) {
            $application->setPublicOptions($definition['publicOptions']);
        }
        
        if (isset($definition['mapEngineCode'])) {
            $application->setMapEngineCode($definition['mapEngineCode']);
        }
        if (isset($definition['persistentView'])) {
            $application->setPersistentView($definition['persistentView']);
        }
 
        if (array_key_exists('extra_assets', $definition)) {
            $application->setExtraAssets($definition['extra_assets']);
        }
        if (!empty($definition['regionProperties'])) {
            $this->parseRegionProperties($application, $definition['regionProperties']);
        }
        if (!empty($definition['elements'])) {
            $collection = new YamlElementCollection($this->elementFactory, $application, $definition['elements'], $this->logger);
            $application->setElements($collection);
        }

        $application->setYamlRoles(array_key_exists('roles', $definition) ? $definition['roles'] : array());
        if ($application->isPublished() && !$application->getYamlRoles()) {
            $application->setYamlRoles(array(
               'IS_AUTHENTICATED_ANONYMOUSLY',
            ));
        }

        foreach ($definition['layersets'] as $layersetId => $layersetDefinition) {
            $layerset = $this->createLayerset($layersetId, $layersetDefinition);
            $layerset->setApplication($application);
            $application->addLayerset($layerset);
        }
        $application->setSource(Application::SOURCE_YAML);
        $this->onLoadListener->postLoad($application);
        return $application;
    }

    /**
     * @param string $layersetId
     * @param mixed[] $layersetDefinition
     * @return Layerset
     */
    protected function createLayerset($layersetId, $layersetDefinition)
    {
        $layerset = new Layerset();
        $layerset
            ->setId($layersetId)
            ->setTitle(strval($layersetId))
        ;
        // Keep default (true) if "selected" is not set
        if (isset($layersetDefinition['selected'])) {
            $layerset->setSelected($layersetDefinition['selected']);
        }
        $layersetProps = array(
            'selected',
        );
        $instanceDefinitions = \array_diff_key($layersetDefinition, \array_flip($layersetProps));
        $instanceCollection = new YamlSourceInstanceCollection($this->sourceTypeDirectory, $layerset, $instanceDefinitions);
        $layerset->setInstances($instanceCollection);
        return $layerset;
    }

    protected function parseRegionProperties(Application $application, array $defs)
    {
        $regions = array();
        foreach ($defs as $k => $spec) {
            // NOTE: cannot detect based on "name", because Fullscreen sidepane
            // actually has a "name" property (=type accordion/tabs/unstyled)
            if (\array_key_exists('properties', $spec)) {
                $regionName = $spec['name'];
                $props = $spec['properties'];
            } else {
                $regionName = $k;
                $props = $spec;
            }
            if (\is_numeric($regionName)) {
                throw new \LogicException("Invalid region name {$regionName} in regionProperties definition for application {$application->getSlug()}");
            }
            if (\in_array($regionName, $regions)) {
                throw new \LogicException("Invalid repeated regionProperties definition of region {$regionName} in application {$application->getSlug()}");
            }
            if (!$props) {
                continue;
            }
            $regions[] = $regionName;

            $regionProperties = new RegionProperties();
            $regionProperties->setId($application->getSlug() . ':' . $regionName);
            $regionProperties->setName($regionName);
            $regionProperties->setProperties($props);
            $regionProperties->setApplication($application);
            $application->addRegionProperties($regionProperties);
        }
    }
}
