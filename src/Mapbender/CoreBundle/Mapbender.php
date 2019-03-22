<?php

namespace Mapbender\CoreBundle;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Component\UploadsManager;
use Mapbender\CoreBundle\Component\YamlApplicationImporter;
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
     * @return string[]
     */
    public function getElements()
    {
        /** @var ElementInventoryService $inventoryService */
        $inventoryService = $this->container->get('mapbender.element_inventory.service');
        return $inventoryService->getActiveInventory();
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
     * Get list of names of all declared template classes.
     *
     * Template classes need to be declared in each bundle's main class
     * getTemplates method.
     *
     * @return string[]
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
            /** @var EntityRepository $repository */
            $entity = $repository->findOneBy(array(
                'slug' => $slug,
            ));
        }

        if ($entity) {
            $entity->setSource(Entity::SOURCE_DB);
        } else {
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
    protected $sourceLayers = array();

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
        $application          = $this->getApplicationEntity($slug);
        $newSlug              = $newSlug ? $newSlug : EntityUtil::getUniqueValue($manager, get_class($application), 'slug', $application->getSlug() . '_yml', '');
        $newTitle             = $newTitle ? $newTitle : EntityUtil::getUniqueValue($manager, get_class($application), 'title', $application->getSlug() . ' YAML', '');
        $elements             = array();
        $lays                 = array();
        $translator           = $this->container->get("translator");

        // @todo: move all the following code into the YamlApplicationImporter service

        $application->setSlug($newSlug);
        $application->setTitle($newTitle);
        $application->setSource(Entity::SOURCE_DB);
        $application->setPublished(true);
        $application->setUpdated(new \DateTime('now'));

        /** @var UploadsManager $ulm */
        $ulm = $this->container->get('mapbender.uploads_manager.service');
        $ulm->copySubdirectory($slug, $newSlug);

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
            $title                     = $translator->trans($elm->getTitle());
            $elm->setTitle($title);
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

                if ($foundedSource == null || !isset($this->sourceLayers[ $srcId ])) {
                    $this->sourceLayers[ $srcId ] = array();
                    $foundedSource              = null;
                } else {
                    $inst->setSource($foundedSource);
                    $source = $foundedSource;
                }

                foreach ($source->getLayers() as $lay) {
                    $manager->persist($lay);
                }

                $manager->persist($source);

                $wmsInstanceLayer = &$this->sourceLayers[ $srcId ];

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

            if ($element->getClass() === 'Mapbender\CoreBundle\Element\BaseSourceSwitcher') {
                if ($config['instancesets']) {
                    foreach ($config['instancesets'] as $instanceSetId => &$instanceSet) {
                        $instances = array();
                        foreach ($instanceSet["instances"] as $instanceNamedId) {
                            foreach ($application->getLayersets() as $appInstanceSet) {
                                foreach ($appInstanceSet->getInstances() as $appInstance) {
                                    $instances = array();
                                    // hack: Title becomes original UID by import
                                    if ($appInstance->getSource()->getTitle() == $instanceNamedId) {
                                        $instances[] = $appInstance->getId();
                                        break;
                                    }
                                }

                            }

                        }
                        $instanceSet["instances"] = $instances;
                    }
                }
            }


            $element->setConfiguration($config);
            $manager->persist($element);
        }

        $manager->flush();
        $connection->commit();

        /** @var YamlApplicationImporter $importerService */
        $importerService = $this->container->get('mapbender.yaml_application_importer.service');
        $importerService->addViewPermissions($application);
    }

    /**
     * Find matching database source
     *
     * @param \Mapbender\WmsBundle\Entity\WmsSource|Source $source
     * @return \Mapbender\WmsBundle\Entity\WmsSource|Source|null
     */
    private function findMatchingSource(Source $source)
    {
        $requiredHash = static::genServiceHash($source->getOriginUrl(), array(
            'version' => $source->getVersion(),
            'service' => $source->getType()
        ));

        /** @var \Mapbender\WmsBundle\Entity\WmsSource|Source $dbSource */
        foreach ($this
                     ->getManager()
                     ->getRepository(get_class($source))
                     ->findAll() as $dbSource) {
            $dbHash = static::genServiceHash($dbSource->getOriginUrl(), array(
                'version' => $dbSource->getVersion(),
                'service' => $source->getType()
            ));

            //echo "Compare hashes: \n"
            //    . "NEW: $requiredHash : ".$source->getId() ." . " . $source->getOriginUrl() . "&version=".$source->getVersion()." \n"
            //    . "DB:  $dbHash : ".$source->getId() ."  " . $dbSource->getOriginUrl() . "&version=".$dbSource->getVersion()."\n";

            if ($dbHash == $requiredHash) {
                return $dbSource;
            }
        }
    }


    /**
     * Gen unique WMS service hash
     *
     * @param string $url
     * @param array  $queryVars
     * @return string
     */
    public static function genServiceHash($url, array $queryVars = array())
    {
        $queryParams = array();
        $baseUrl   = $url;
        $query     = '';

        if (strpos('?', $url)) {
            list($baseUrl, $query) = explode('?', $url);
        }

        parse_str($query, $queryParams);

        foreach ($queryParams as $key => $value) {
            $queryVars[ mb_strtolower($key) ] = trim($value);
        }

        // remove default request
        unset($queryVars["request"]);

        if (!isset($queryVars["service"])) {
            $queryVars["service"] = "WMS";
        }

        if (!isset($queryVars["version"])) {
            $queryVars["version"] = "1.1.1";
        }

        $details     = parse_url($baseUrl);

        asort($queryVars);

        return md5($details['host'] . $details["path"] . "?" . http_build_query($queryVars));
    }
}
