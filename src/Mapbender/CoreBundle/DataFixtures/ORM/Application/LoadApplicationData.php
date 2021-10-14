<?php

namespace Mapbender\CoreBundle\DataFixtures\ORM\Application;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Mapbender;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class LoadApplicationData imports YAML defined applications into a mapbender database.
 *
 * @author Paul Schmidt
 */
class LoadApplicationData implements FixtureInterface, ContainerAwareInterface
{
    /** @var ContainerInterface $container */
    private $container;

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param \Doctrine\ORM\EntityManager|ObjectManager $manager
     */
    public function load(ObjectManager $manager = null)
    {
        /** @var Mapbender $core */
        $core = $this->container->get("mapbender");
        /** @var ApplicationYAMLMapper $repository */
        $repository = $this->container->get('mapbender.application.yaml_entity_repository');
        foreach ($repository->getApplications() as $application) {
            if ($application->isPublished()) {
                $core->importYamlApplication($application->getSlug());
            }
        }
    }
}