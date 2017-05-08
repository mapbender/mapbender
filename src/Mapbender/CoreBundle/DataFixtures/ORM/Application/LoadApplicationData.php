<?php

namespace Mapbender\CoreBundle\DataFixtures\ORM\Application;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
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
        $core = $this->container->get("mapbender");
        foreach ($core->getYamlApplicationEntities(true) as $slug => $application) {
            $core->importYamlApplication($slug);
        }
    }
}
