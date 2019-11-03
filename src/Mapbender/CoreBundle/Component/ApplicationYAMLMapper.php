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
    /** @var ElementFactory */
    protected $elementFactory;
    /** @var array[] */
    protected $definitions;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->elementFactory = $container->get('mapbender.element_factory.service');
        $this->sourceTypeDirectory = $container->get('mapbender.source.typedirectory.service');
        $this->definitions = $container->getParameter('applications');
        $this->logger = $container->get('logger');
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
        $application = $this->createApplication($this->definitions[$slug]);
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
        $instanceCollection = new YamlSourceInstanceCollection($this->sourceTypeDirectory, $layerset, $layersetDefinition);
        $layerset->setInstances($instanceCollection);
        return $layerset;
    }
}
