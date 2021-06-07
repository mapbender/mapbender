<?php

namespace Mapbender\CoreBundle\DataFixtures\ORM\Application;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mapbender\CoreBundle\Command\ApplicationImportCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class LoadApplicationData imports YAML defined applications into a mapbender database.
 *
 * @author Paul Schmidt
 *
 * @deprecated for incompatibility with Symfony 4; misuse of fixtures for non-test-related usages
 * Replace with app/console mapbender:application:import <directoryname>
 */
class LoadApplicationData implements FixtureInterface, ContainerAwareInterface
{
    /** @var ApplicationImportCommand */
    protected $importCommand;
    /** @var string[] */
    protected $sourcePaths;

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->sourcePaths = $container->getParameter('mapbender.yaml_application_dirs');
        $this->importCommand = $container->get('mapbender.command.application_import');
    }

    /**
     * @param \Doctrine\ORM\EntityManager|ObjectManager $manager
     */
    public function load(ObjectManager $manager = null)
    {
        $fakeInput = new ArrayInput(array());
        $output = new SymfonyStyle($fakeInput, new ConsoleOutput());
        $output->warning('DEPRECATED: importing applications via fixture is deprecated and will be removed in Mapbender v3.3. Use the mapbender:application:import console command instead, using a directory name as an argument.');
        foreach ($this->sourcePaths as $sourcePath) {
            $this->importCommand->processDirectory($sourcePath, $fakeInput, $output);
        }
    }
}
