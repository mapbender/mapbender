<?php

namespace Mapbender\CoreBundle;

use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mapbender - The central Mapbender3 service(core). Provides metadata about
 * available elements and templates.
 *
 * @author Christian Wygoda
 * @author Andriy Oblivantsev
 */
class Mapbender
{
    /** @var \Doctrine\ORM\EntityManager|\Doctrine\Common\Persistence\ObjectManager */
    protected $manager;
    /** @var ApplicationYAMLMapper */
    protected $yamlMapper;
    /** @var ImportHandler */
    protected $importer;

    /** @var ContainerInterface */
    private $container;

    /** @var string[] */
    private $templates = array();

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
        $this->container  = $container;
        $this->yamlMapper = $container->get('mapbender.application.yaml_entity_repository');
        $this->importer = $container->get('mapbender.application_importer.service');

        /** @var MapbenderBundle $bundle */
        foreach ($bundles as $bundle) {
            if (!is_subclass_of($bundle, 'Mapbender\CoreBundle\Component\MapbenderBundle')) {
                continue;
            }
            $this->templates          = array_merge($this->templates, $bundle->getTemplates());
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
     * Get YAML application entities
     *
     * @return Application[]
     */
    public function getYamlApplicationEntities()
    {
        return $this->yamlMapper->getApplications();
    }

    /**
     * @param string $slug
     * @return Application
     */
    public function getYamlApplication($slug)
    {
        return $this->yamlMapper->getApplication($slug);
    }

    /**
     * Import YAML application
     *
     * @param  string     $slug     Source application slug
     */
    public function importYamlApplication($slug)
    {
        $application = $this->yamlMapper->getApplication($slug);
        $newSlug = EntityUtil::getUniqueValue($this->manager, get_class($application), 'slug', $application->getSlug() . '_yml', '');
        $newTitle = EntityUtil::getUniqueValue($this->manager, get_class($application), 'title', $application->getTitle(), ' ');

        $this->manager->beginTransaction();
        $clonedApp = $this->importer->duplicateApplication($application, $newSlug);
        $clonedApp->setTitle($newTitle);
        $this->manager->commit();
    }
}
