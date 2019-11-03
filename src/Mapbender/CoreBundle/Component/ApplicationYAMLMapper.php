<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\Component\Collections\YamlElementCollection;
use Mapbender\Component\Collections\YamlSourceInstanceCollection;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts array-style application definitions to Application entities.
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
        $applications = array();
        foreach ($this->getDefinitions() as $slug => $def) {
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
        $definitions = $this->getDefinitions();
        if (!array_key_exists($slug, $definitions)) {
            return null;
        }
        $application = $this->createApplication($definitions[$slug]);
        $application->setId($slug);
        $application->setSlug($slug);
        return $application;
    }

    protected function createApplication($definition)
    {
        $timestamp = filemtime($definition['__filename__']);
        unset($definition['__filename__']);
        if (!array_key_exists('title', $definition)) {
            $definition['title'] = "TITLE " . $timestamp;
        }

        $application = new Application();
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

        if (array_key_exists('extra_assets', $definition)) {
            $application->setExtraAssets($definition['extra_assets']);
        }
        if (array_key_exists('regionProperties', $definition)) {
            foreach ($definition['regionProperties'] as $index => $regProps) {
                $regionProperties = new RegionProperties();
                $regionProperties->setId($application->getSlug() . ':' . $index);
                $regionProperties->setName($regProps['name']);
                $regionProperties->setProperties($regProps['properties']);
                $regionProperties->setApplication($application);
                $application->addRegionProperties($regionProperties);
            }
        }
        if (!empty($definition['elements'])) {
            $collection = new YamlElementCollection($this->getElementFactory(), $application, $definition['elements'], $this->logger);
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
        /** @var TypeDirectoryService $typeDirectory */
        $typeDirectory = $this->container->get('mapbender.source.typedirectory.service');
        $instanceCollection = new YamlSourceInstanceCollection($typeDirectory, $layerset, $layersetDefinition);
        $layerset->setInstances($instanceCollection);
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

    /**
     * @return array[]
     */
    protected function getDefinitions()
    {
        return $this->container->getParameter('applications');
    }
}
