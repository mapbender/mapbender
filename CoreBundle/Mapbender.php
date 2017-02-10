<?php

namespace Mapbender\CoreBundle;

use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Entity\Application as Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mapbender - The central Mapbender3 service(core). Provides metadata about
 * available elements, layers and templates.
 *
 * @author Christian Wygoda
 * @author Andriy Oblivantsev
 */
class Mapbender
{
    /** @var ContainerInterface  */
    private $container;

    /** @var Element[] */
    private $elements           = array();
    private $layers             = array();
    private $templates          = array();
    private $repositoryManagers = array();

    /**
     * Mapbender constructor.
     *
     * Iterate over all bundles and if is an MapbenderBundle, get list
     * of elements, layers and templates.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $bundles = $container->get('kernel')->getBundles();
        foreach($bundles as $bundle) {
            if(is_subclass_of($bundle,
                'Mapbender\CoreBundle\Component\MapbenderBundle')) {

                $this->elements = array_merge($this->elements,
                    $bundle->getElements());
                $this->layer =  array_merge($this->layers,
                    $bundle->getLayers());
                $this->templates = array_merge($this->templates,
                    $bundle->getTemplates());
                $this->repositoryManagers = array_merge($this->repositoryManagers,
                    $bundle->getRepositoryManagers());
            }
        }
    }

    /**
     * Get list of all declared element classes.
     *
     * Element classes need to be declared in each bundle's main class getElement
     * method.
     *
     * @return array
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Get list of all declared source factories.
     *
     * @return array
     */
    public function getRepositoryManagers()
    {
        return $this->repositoryManagers;
    }

    /**
     * Get list of all declared layer classes.
     *
     * Layer classes need to be declared in each bundle's main class getLayers
     * method.
     *
     * @return array
     */
    public function getLayers()
    {
        return $this->layers;
    }

    /**
     * Get list of all declared template classes.
     *
     * Template classes need to be declared in each bundle's main class
     * getTemplates method.
     *
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Get the application for the given slug.
     *
     * Returns either application if it exists, null otherwise. If two
     * applications with the same slug exist, the database one will
     * override the YAML one.
     *
     * @return Application
     */
    public function getApplication($slug, $urls)
    {
        $entity = $this->getApplicationEntity($slug);
        if (!$entity) {
            return null;
        }

        return new Application($this->container, $entity, $urls);
    }

    /**
     * Get application entities
     *
     * @return Entity[]
     */
    public function getApplicationEntities()
    {
        /** @var Entity[] $dbApplications */
        $applications = array();
        $yamlMapper   = new ApplicationYAMLMapper($this->container);
        $registry     = $this->container->get('doctrine');
        foreach ($yamlMapper->getApplications() as $application) {
            if (!$application->isPublished()) {
                continue;
            }
            $applications[ $application->getSlug() ] = $application;
        }
        $dbApplications = $registry->getManager()
            ->createQuery("SELECT a From MapbenderCoreBundle:Application a  ORDER BY a.title ASC")
            ->getResult();
        foreach ($dbApplications as $application) {
            $application->setSource(Entity::SOURCE_DB);
            $applications[ $application->getSlug() ] = $application;
        }

        return $applications;
    }

    /**
     * Get application entity for given slug
     *
     * @return Entity|null
     */
    public function getApplicationEntity($slug) {
        $entity = $this->container->get('doctrine')
            ->getRepository('MapbenderCoreBundle:Application')
            ->findOneBySlug($slug);
        if($entity) {
            $entity->setSource(Entity::SOURCE_DB);
            return $entity;
        }

        $yamlMapper = new ApplicationYAMLMapper($this->container);
        $entity = $yamlMapper->getApplication($slug);
        if(!$entity || !$entity->isPublished()) {
            return null;
        }
        return $entity;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:map.html.twig';
    }
}
