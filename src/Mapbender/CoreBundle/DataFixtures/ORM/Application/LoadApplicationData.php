<?php

namespace Mapbender\CoreBundle\DataFixtures\ORM\Application;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class LoadApplicationData imports YAML defined applications into a mapbender database.
 *
 * @author Paul Schmidt
 *
 * @todo Sf4: figure out if Fixtures can use service DI
 */
class LoadApplicationData implements FixtureInterface, ContainerAwareInterface
{
    /** @var ApplicationYAMLMapper */
    protected $repository;
    /** @var ImportHandler */
    protected $importer;

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->repository = $container->get('mapbender.application.yaml_entity_repository');
        $this->importer = $container->get('mapbender.application_importer.service');
    }

    /**
     * @param \Doctrine\ORM\EntityManager|ObjectManager $manager
     */
    public function load(ObjectManager $manager = null)
    {
        foreach ($this->repository->getApplications() as $application) {
            if ($application->isPublished()) {
                $this->importOne($manager, $application);
            }
        }
    }

    protected function importOne(EntityManagerInterface $em, Application $application)
    {
        $newSlug = EntityUtil::getUniqueValue($em, get_class($application), 'slug', $application->getSlug() . '_yml', '');
        $newTitle = EntityUtil::getUniqueValue($em, get_class($application), 'title', $application->getTitle(), ' ');
        $em->beginTransaction();
        $clonedApp = $this->importer->duplicateApplication($application, $newSlug);
        $clonedApp->setTitle($newTitle);
        $em->commit();
        if (\php_sapi_name() === 'cli') {
            echo "Created database application {$clonedApp->getSlug()}\n";
        }
    }
}
