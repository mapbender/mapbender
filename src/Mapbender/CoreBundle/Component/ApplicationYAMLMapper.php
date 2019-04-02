<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * YAML mapper for applications
 *
 * This class is responsible for mapping application definitions given in the
 * YAML configuration to Application configuration entities.
 *
 * @author Christian Wygoda
 */
class ApplicationYAMLMapper
{
    /** @var LoggerInterface  */
    protected $logger;
    /**
     * The service container
     * @var ContainerInterface
     */
    private $container;

    /**
     * ApplicationYAMLMapper constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get("logger");
    }

    /**
     * Get all YAML applications
     *
     * @return Application[]
     */
    public function getApplications()
    {
        $definitions  = $this->container->getParameter('applications');
        $applications = array();
        foreach ($definitions as $slug => $def) {
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
     * @return Application
     */
    public function getApplication($slug)
    {

        $definitions = $this->container->getParameter('applications');
        if (!array_key_exists($slug, $definitions)) {
            return null;
        }
        $timestamp = round((microtime(true) * 1000));
        $definition = $definitions[$slug];
        if (!key_exists('title', $definition)) {
            $definition['title'] = "TITLE " . $timestamp;
        }

        if (!key_exists('published', $definition)) {
            $definition['published'] = false;
        } else {
            $definition['published'] = (boolean) $definition['published'];
        }

        // First, create an application entity
        $application = new Application();
        $application
                ->setScreenshot(key_exists("screenshot", $definition) ? $definition['screenshot'] : null)
                ->setSlug($slug)
                ->setTitle(isset($definition['title'])?$definition['title']:'')
                ->setDescription(isset($definition['description'])?$definition['description']:'')
                ->setTemplate($definition['template'])
                ->setExcludeFromList(isset($definition['excludeFromList'])?$definition['excludeFromList']:false)
                ->setPublished($definition['published']);
        if (isset($definition['custom_css'])) {
            $application->setCustomCss($definition['custom_css']);
        }

        if (isset($definition['publicOptions'])) {
            $application->setPublicOptions($definition['publicOptions']);
        }

        if (array_key_exists('extra_assets', $definition)) {
            $application->setExtraAssets($definition['extra_assets']);
        }
        if (key_exists('regionProperties', $definition)) {
            foreach ($definition['regionProperties'] as $regProps) {
                $regionProperties = new RegionProperties();
                $regionProperties->setName($regProps['name']);
                $regionProperties->setProperties($regProps['properties']);
                $application->addRegionProperties($regionProperties);
            }
        }

        if (!isset($definition['elements'])) {
            $definition['elements'] = array();
        }

        foreach ($definition['elements'] as $region => $elementsDefinition) {
            $weight = 0;
            foreach ($elementsDefinition ?: array() as $id => $elementDefinition) {
                $element = $this->createElement($id, $region, $elementDefinition);
                if (!$element) {
                    continue;
                }
                $element->setWeight($weight++);
                $element->setApplication($application);
                $element->setYamlRoles(array_key_exists('roles', $elementDefinition) ? $elementDefinition['roles'] : array());
                $application->addElement($element);
            }
        }

        $application->setYamlRoles(array_key_exists('roles', $definition) ? $definition['roles'] : array());

        if (!isset($definition['layersets'])) {
            $definition['layersets'] = array();
            if (isset($definition['layerset'])) {
                // @todo: add strict mode support and throw if enabled
                @trigger_error("Deprecated: your YAML application defines legacy 'layerset' (single item), should define 'layersets' (array)", E_USER_DEPRECATED);
                $definition['layersets'][] = $definition['layerset'];
            }
        }
        foreach ($definition['layersets'] as $layersetId => $layersetDefinition) {
            $layerset = $this->createLayerset($layersetId, $layersetDefinition);
            $layerset->setApplication($application);
            $application->addLayerset($layerset);
        }
        $application->setSource(Application::SOURCE_YAML);
        return $application;
    }

    /**
     * @param string $id
     * @param string $region
     * @param mixed[] $elementDefinition
     * @return Element
     */
    protected function createElement($id, $region, $elementDefinition)
    {
        /**
         * MAP Layersets handling
         */
        if ($elementDefinition['class'] == "Mapbender\\CoreBundle\\Element\\Map") {
            if (!isset($elementDefinition['layersets'])) {
                $elementDefinition['layersets'] = array();
            }
            if (isset($elementDefinition['layerset'])) {
                // @todo: add strict mode support and throw if enabled
                @trigger_error("Deprecated: your YAML Map Element defines legacy 'layerset' (single item), should define 'layersets' (array)", E_USER_DEPRECATED);
                $elementDefinition['layersets'][] = $elementDefinition['layerset'];
            }
        }

        $configuration = $elementDefinition;
        unset($configuration['class']);
        unset($configuration['title']);
        try {
            $element = $this->getElementFactory()->newEntity($elementDefinition['class'], $region);
            $element->setConfiguration($configuration);
            $element->setId($id);
            $elComp = $this->getElementFactory()->componentFromEntity($element);
            $title = ArrayUtil::getDefault($elementDefinition, 'title', $elComp->getTitle());
            if ($elComp::$merge_configurations) {
                // Configuration may already have been modified once implicitly
                /** @see ConfigMigrationInterface */
                $configBefore = $element->getConfiguration();
                $configAfter = $elComp->mergeArrays($elComp->getDefaultConfiguration(), $configBefore);
                $element->setConfiguration($configAfter);
            }
            $element->setTitle($title);
            return $element;
        } catch (ElementErrorException $e) {
            // @todo: add strict mode support and throw if enabled
            $this->logger->warning("Your YAML application contains an invalid Elemenet {$elementDefinition['class']}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * @param string $layersetId
     * @param mixed[] $layersetDefinition
     * @return Layerset
     */
    protected function createLayerset($layersetId, $layersetDefinition)
    {
        // TODO: Add roles
        $layerset = new Layerset();
        $layerset
            ->setId($layersetId)
            ->setTitle(strval($layersetId))
        ;

        $weight = 0;
        foreach ($layersetDefinition as $layerId => $layerDefinition) {
            $class = $layerDefinition['class'];
            unset($layerDefinition['class']);
            $instance = new $class();
            $entityHandler = SourceInstanceEntityHandler::createHandler($this->container, $instance);
            $layerParams = array_merge($layerDefinition, array(
                'weight'   => $weight++,
                "id"       => $layerId,
                "layerset" => $layerset,
            ));
            $entityHandler->setParameters($layerParams);
            $layerset->addInstance($instance);
        }
        return $layerset;
    }

    /**
     * @return ElementFactory
     */
    protected function getElementFactory()
    {
        /** @var ElementFactory $service */
        $service = $this->container->get('mapbender.element_factory.service');
        return $service;
    }
}
