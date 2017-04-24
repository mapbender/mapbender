<?php

namespace Mapbender\CoreBundle;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application as Entity;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
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
    /** @var \Doctrine\ORM\EntityManager|\Doctrine\Common\Persistence\ObjectManager */
    protected $manager;

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

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
        $bundles          = $container->get('kernel')->getBundles();
        $registry         = $container->get('doctrine');
        $this->manager    = $registry->getManager();
        $this->connection = $this->manager->getConnection();
        $this->container  = $container;

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
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|\Doctrine\ORM\EntityManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Get the application for the given slug.
     *
     * Returns either application if it exists, null otherwise. If two
     * applications with the same slug exist, the database one will
     * override the YAML one.
     *
     * @param string $slug
     * @param array $urls Array of runtime URLs
     * @return Application Application component
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
     * @return \Mapbender\CoreBundle\Entity\Application[]
     */
    public function getApplicationEntities()
    {
        return array_merge(
            $this->getYamlApplicationEntities(true),
            $this->getDatabaseApplicationEntities()
        );
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

    /**
     * Get public YAML application entities
     *
     * @param bool $onlyPublic Only public applications?
     * @return Component\Application[]
     */
    public function getYamlApplicationEntities($onlyPublic = true)
    {
        $applications = array();
        $yamlMapper   = new ApplicationYAMLMapper($this->container);
        foreach ($yamlMapper->getApplications() as $application) {

            // Exclude administration applications
            if (strpos($application->getTemplate(), "Mapbender\\ManagerBundle") === 0) {
                continue;
            }

            if ($onlyPublic && !$application->isPublished()) {
                continue;
            }

            $applications[ $application->getSlug() ] = $application;
        }
        return $applications;
    }

    /**
     * Get data base entities
     *
     * @return Application[]
     */
    public function getDatabaseApplicationEntities()
    {
        /** @var \Mapbender\CoreBundle\Entity\Application $application */
        $applications = array();
        foreach ($this->getManager()
                     ->createQuery("SELECT a FROM MapbenderCoreBundle:Application a ORDER BY a.title ASC")
                     ->getResult() as $application) {
            $application->setSource(Entity::SOURCE_DB);
            $applications[ $application->getSlug() ] = $application;
        }
        return $applications;
    }

    /** @var \Mapbender\WmsBundle\Entity\WmsInstanceLayer[][]|ArrayCollection */
    protected $sourceLays = array();

    /**
     * Import YAML application
     *
     * @param  string     $slug     Source application slug
     * @param string|null $newSlug  New applicatio n slug name
     * @param string|null $newTitle New application title name
     */
    public function importYamlApplication($slug, $newSlug = null, $newTitle = null)
    {
        /** @var Layerset[] $lays */
        /** @var \Mapbender\CoreBundle\Entity\Element[] $elms */

        $manager              = $this->getManager();
        $connection           = $this->getConnection();
        $applicationComponent = $this->getApplication($slug, array());
        $application          = $applicationComponent->getEntity();
        $newSlug              = $newSlug ? $newSlug : EntityUtil::getUniqueValue($manager, get_class($application), 'slug', $application->getSlug() . '_yml', '');
        $newTitle             = $newTitle ? $newTitle : EntityUtil::getUniqueValue($manager, get_class($application), 'title', $application->getSlug() . ' YAML', '');
        $elements = array();
        $lays     = array();

        $application->setSlug($newSlug);
        $application->setTitle($newTitle);
        $application->setSource(Entity::SOURCE_DB);
        $application->setPublished(true);
        $application->setUpdated(new \DateTime('now'));

        Application::createAppWebDir($this->container, $application->getSlug());
        Application::copyAppWebDir($this->container, $slug, $newSlug);

        $connection->beginTransaction();

        /**
         * Save application
         */
        $manager->persist($application);

        /**
         * Save region properties
         */
        foreach ($application->getRegionProperties() as $prop) {
            $prop->setApplication($application);
            $manager->persist($prop);
        }

        /**
         * Save elements
         */
        foreach ($application->getElements() as $elm) {
            $elements[ $elm->getId() ] = $elm;
            $manager->persist($elm);
        }

        /**
         * Save layer sets
         */
        foreach ($application->getLayersets() as $set) {
            $lays[ $set->getId() ] = $set;
            $manager->persist($set);
            foreach ($set->getInstances() as $inst) {
                $source        = $inst->getSource();
                $srcId         = $source->getId();
                $foundedSource = $this->findMatchingSource($source);

                if ($foundedSource == null || !isset($this->sourceLays[ $srcId ])) {
                    $this->sourceLays[ $srcId ] = array();
                    $foundedSource              = null;
                } else {
                    $inst->setSource($foundedSource);
                    $source = $foundedSource;
                }

                foreach ($source->getLayers() as $lay) {
                    $manager->persist($lay);
                }

                $manager->persist($source);

                $wmsInstanceLayer = &$this->sourceLays[ $srcId ];

                foreach ($inst->getLayers() as $lay) {
                    if ($foundedSource != null) {
                        $instanceLayer = $wmsInstanceLayer[ $lay->getId() ];
                        $lay->setSourceItem($instanceLayer->getSourceItem());
                    } else {
                        $wmsInstanceLayer[ $lay->getId() ] = $lay;
                    }
                    $manager->persist($lay);
                }

                $manager->persist($inst);
            }
        }

        // Saves layers and need to get ID's
        $manager->flush();

        /**
         * Post update element configurations
         */
        foreach ($elements as $element) {
            $config = $element->getConfiguration();
            if (isset($config['target'])) {
                $elm              = $elements[ $config['target'] ];
                $config['target'] = $elm->getId();
            }
            if (isset($config['layersets'])) {
                $layerSets = array();
                foreach ($config['layersets'] as $layerSetId) {
                    $layerSet    = $lays[ $layerSetId ];
                    $layerSets[] = $layerSet->getId();
                }
                $config['layersets'] = $layerSets;

            }
            if (isset($config['layerset'])) {
                $layerSet           = $lays[ $config['layerset'] ];
                $config['layerset'] = $layerSet->getId();
            }
            $element->setConfiguration($config);
            $manager->persist($element);
        }

        $manager->flush();
        $connection->commit();

        $applicationComponent->addViewPermissions();
    }

    /**
     * Find matching database source
     *
     * @param \Mapbender\WmsBundle\Entity\WmsSource|Source $source
     * @return \Mapbender\WmsBundle\Entity\WmsSource|Source|null
     */
    private function findMatchingSource(Source $source)
    {
        /** @var \Mapbender\WmsBundle\Entity\WmsSource|Source $dbSource */
        foreach ($this
                     ->getManager()
                     ->getRepository(get_class($source))
                     ->findBy(
                         array(
                             'originUrl' => $source->getOriginUrl()
                         )
                     ) as $dbSource) {

            $dbLayers = $dbSource->getLayers();
            $layers   = $source->getLayers();
            if ($dbLayers->count() !== $layers->count()) {
                continue;
            }

            foreach ($layers as $layer) {
                foreach ($dbLayers as $dbLayer) {
                    var_dump($dbLayer->getTitle());
                    if ($dbLayer->getTitle() == $layer->getTitle()) {
                        return $dbSource;
                    }
                }
            }
        }
    }
}
