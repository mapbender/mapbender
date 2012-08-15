<?php

/**
 * TODO: License
 */

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Layer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * YAML mapper for applications
 *
 * This class is responsible for mapping application definitions given in the
 * YAML configuration to Application configuration entities.
 *
 * @author Christian Wygoda
 */
class ApplicationYAMLMapper {
    /**
     * The service container
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    /**
     * Get all YAML applications
     *
     * @return array
     */
    public function getApplications() {
        $definitions = $this->container->getParameter('applications');

        $applications = array();
        foreach($definitions as $slug => $def) {
            $application = $this->getApplication($slug);
            if($application !== null) {
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
    public function getApplication($slug) {
        $definitions = $this->container->getParameter('applications');
        if(!array_key_exists($slug, $definitions)) {
            return null;
        }

        $definition = $definitions[$slug];

        // First, create an application entity
        $application = new Application();
        $application
            ->setSlug($slug)
            ->setTitle($definition['title'])
            ->setDescription($definition['description'])
            ->setTemplate($definition['template'])
            ->setPublished($definition['published']);

        // Then create elements
        foreach($definition['elements'] as $region => $elementsDefinition) {
            $weight = 0;
            foreach($elementsDefinition as $id => $elementDefinition) {
                $configuration = $elementDefinition;
                unset($configuration['class']);
                unset($configuration['title']);

                $class = $elementDefinition['class'];
                $title = array_key_exists('title', $elementDefinition) ?
                    $elementDefinition['title'] :
                    $class::getClassTitle();

                $element = new Element();
                $element
                    ->setId($id)
                    ->setClass($elementDefinition['class'])
                    ->setTitle($title)
                    ->setConfiguration($configuration)
                    ->setRegion($region)
                    ->setWeight($weight++)
                    ->setApplication($application);
                //TODO: Roles
                $application->addElements($element);
            }
        }

        $owner = $this->container->get('doctrine')
            ->getRepository('FOMUserBundle:User')
            ->find(1);
        $application->setOwner($owner);
        // TODO: Add roles, entity needs work first

        // Create layersets and layers
        foreach($definition['layersets'] as $id => $layerDefinitions) {
            $layerset = new Layerset();
            $layerset
                ->setId($id)
                ->setTitle('YAML - ' . $id)
                ->setApplication($application);

            $weight = 0;
            foreach($layerDefinitions as $id => $layerDefinition) {
                $configuration = $layerDefinition;
                unset($configuration['class']);
                unset($configuration['title']);

                $layer = new Layer();
                $layer
                    ->setId($id)
                    ->setClass($layerDefinition['class'])
                    ->setTitle($layerDefinition['title'])
                    ->setConfiguration($configuration)
                    ->setWeight($weight++)
                    ->setLayerset($layerset);

                $layerset->addLayers($layer);
            }
            $application->addLayersets($layerset);
        }

        $application->setSource(Application::SOURCE_YAML);

        return $application;
    }
}

