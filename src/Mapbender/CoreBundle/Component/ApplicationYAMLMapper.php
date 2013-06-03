<?php

/**
 * TODO: License
 */

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Application as ApplicationEntity;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Component\Element as ElementComponent;
use Mapbender\CoreBundle\Entity\Layerset;
//use Mapbender\CoreBundle\Entity\Layer;
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

    /**
     * The service container
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get all YAML applications
     *
     * @return array
     */
    public function getApplications()
    {
        $definitions = $this->container->getParameter('applications');

        $applications = array();
        foreach($definitions as $slug => $def)
        {
            $application = $this->getApplication($slug);
            if($application !== null)
            {
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
        if(!array_key_exists($slug, $definitions))
        {
            return null;
        }
        $timestamp = round((microtime(true) * 1000));
        $definition = $definitions[$slug];
        if(!key_exists('title', $definition))
        {
            $definition['title'] = "TITLE ". $timestamp;
        }

        if(!key_exists('published', $definition))
        {
            $definition['published'] = false;
        } else 
        {
            $definition['published'] = (boolean) $definition['published'];
        }

        // First, create an application entity
        $application = new ApplicationEntity();
        $application
                ->setSlug($slug)
                ->setTitle($definition['title'])
                ->setDescription($definition['description'])
                ->setTemplate($definition['template'])
                ->setPublished($definition['published']);

        if(array_key_exists('extra_assets', $definition))
        {
            $application->setExtraAssets($definition['extra_assets']);
        }

        // Then create elements
        foreach($definition['elements'] as $region => $elementsDefinition)
        {
            $weight = 0;
            if($elementsDefinition !== null)
            {
                foreach($elementsDefinition as $id => $elementDefinition)
                {
                    $configuration_ = $elementDefinition;
                    unset($configuration_['class']);
                    unset($configuration_['title']);
                    $entity_class = $elementDefinition['class'];
                    $appl = new \Mapbender\CoreBundle\Component\Application($this->container, $application, array());
                    $elComp = new $entity_class($appl, $this->container, new \Mapbender\CoreBundle\Entity\Element());
                    $defConfig = $elComp->getDefaultConfiguration();
                    $configuration = ElementComponent::mergeArrays($elComp->getDefaultConfiguration(), $configuration_, array());

                    $class = $elementDefinition['class'];
                    $title = array_key_exists('title', $elementDefinition) ?
                            $elementDefinition['title'] :
                            $class::getClassTitle();

                    $element = new Element();
//                    $elComp = new ElementComponent()
                   
                    $element->setId($id)
                            ->setClass($elementDefinition['class'])
                            ->setTitle($title)
                            ->setConfiguration($configuration)
                            ->setRegion($region)
                            ->setWeight($weight++)
                            ->setApplication($application);

                    if(array_key_exists('roles', $elementDefinition)) {
                        $securityContext = $this->container->get('security.context');
                        $passed = false;
                        foreach($elementDefinition['roles'] as $role) {
                            if($securityContext->isGranted($role)) {
                                $passed = true;
                                break;
                            }
                        }
                        if(!$passed) {
                            continue;
                        }
                    }
                    
                    //TODO: Roles
                    $application->addElements($element);
                }
            }
        }

        $owner = $this->container->get('doctrine')
                ->getRepository('FOMUserBundle:User')
                ->find(1);
        $application->setOwner($owner);

        $application->yaml_roles = array();
        if(array_key_exists('roles', $definition)) {
            $application->yaml_roles = $definition['roles'];
        }
        
        // TODO: Add roles, entity needs work first
        // Create layersets and layers
        foreach($definition['layersets'] as $id => $layerDefinitions)
        {
            $layerset = new Layerset();
            $layerset
                    ->setId($id)
                    ->setTitle('YAML - ' . $id)
                    ->setApplication($application);

            $weight = 0;
            foreach($layerDefinitions as $id => $layerDefinition)
            {
                $class = $layerDefinition['class'];
                unset($layerDefinition['class']);
                $instance = new $class();
                $instance->setId($id)
                        ->setTitle($layerDefinition['title'])
                        ->setWeight($weight++)
                        ->setLayerset($layerset)
                        ->setProxy(!isset($layerDefinition['proxy']) ? false : $layerDefinition['proxy'])
                        ->setVisible(!isset($layerDefinition['visible']) ? true : $layerDefinition['visible'])
                        ->setFormat(!isset($layerDefinition['format']) ? true : $layerDefinition['format'])
                        ->setInfoformat(!isset($layerDefinition['info_format']) ? null : $layerDefinition['info_format'])
                        ->setTransparency(!isset($layerDefinition['transparent']) ? true : $layerDefinition['transparent'])
                        ->setOpacity(!isset($layerDefinition['opacity']) ? 100 : $layerDefinition['opacity'])
                        ->setTiled(!isset($layerDefinition['tiled']) ? false : $layerDefinition['tiled'])
                        ->setConfiguration($layerDefinition);
                $layerset->addInstance($instance);
            }
            $application->addLayerset($layerset);
        }

        $application->setSource(ApplicationEntity::SOURCE_YAML);

        return $application;
    }

}

