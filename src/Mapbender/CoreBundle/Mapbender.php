<?php

namespace Mapbender\CoreBundle;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\CoreBundle\Component\Template;
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
    /** @var ContainerInterface */
    private $container;

    /** @var Element[] */
    private $elements = array();

    /** @var array */
    private $layers = array();

    /** @var Template[] */
    private $templates = array();

    /** @var array */
    private $repositoryManagers = array();

    /**
     * Mapbender constructor.
     *
     * Iterate over all bundles and if is an MapbenderBundle, get list
     * of elements, layers and templates.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $bundles = $container->get('kernel')->getBundles();

        /** @var MapbenderBundle $bundle */
        foreach ($bundles as $bundle) {
            if (!is_subclass_of($bundle, 'Mapbender\CoreBundle\Component\MapbenderBundle')) {
                continue;
            }

            $this->elements           = array_merge($this->elements, $bundle->getElements());
            $this->layer              = array_merge($this->layers, $bundle->getLayers());
            $this->templates          = array_merge($this->templates, $bundle->getTemplates());
            $this->repositoryManagers = array_merge($this->repositoryManagers, $bundle->getRepositoryManagers());
        }
    }

    /**
     * Get list of all declared element classes.
     *
     * Element classes need to be declared in each bundle's main class getElement
     * method.
     *
     * @return Element[]
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
     * @return Template[]
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
     * @param string $slug
     * @return Application Application component
     */
    public function getApplication($slug)
    {
        $entity = $this->getApplicationEntity($slug);
        if (!$entity) {
            return null;
        }

        return new Application($this->container, $entity);
    }

    /**
     * Get application entities
     *
     * @return \Mapbender\CoreBundle\Entity\Application[]
     */
    public function getApplicationEntities()
    {
        /** @var \Mapbender\CoreBundle\Entity\Application $application */
        /** @var Application[] $dbApplications */
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
     * @param string $slug
     * @return \Mapbender\CoreBundle\Entity\Application
     */
    public function getApplicationEntity($slug)
    {
        /** @var \Mapbender\CoreBundle\Entity\Application $entity */
        $registry   = $this->container->get('doctrine');
        $repository = $registry->getRepository('MapbenderCoreBundle:Application');

        if ($repository instanceof EntityRepository) {
            /** @var EntityRepository $repository  Sometimes findOneBySlug method is there, but this is a magic */
            $entity = $repository->findOneBySlug($slug);
        }

        if ($entity) {
            $entity->setSource(Entity::SOURCE_DB);
        } else {
            /** @var ObjectRepository $repository */
            $yamlMapper = new ApplicationYAMLMapper($this->container);
            $entity     = $yamlMapper->getApplication($slug);
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
