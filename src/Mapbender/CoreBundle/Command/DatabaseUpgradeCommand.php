<?php

namespace Mapbender\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class DatabaseUpgradeCommand extends Command
{

    /** @var ManagerRegistry */
    protected $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        parent::__construct(null);
    }

    protected function configure() {
        $this
            ->setHelp('The <info>mapbender:database:upgrade</info> command updates the database to the new schema of your mapbender version')
            ->setDescription('Removes outdated element configuration values');
    }


    /**
     * Execute command
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->updateMapElementConfigs($input, $output);
    }

    protected function getObsoleteMapOptionNames()
    {
        return array(
            'imgPath',
            'wmsTileDelay',
            'minTileSize',
            'maxResolution',
        );
    }

    /**
     * Prunes obsolete configuration options from map elements
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function updateMapElementConfigs(InputInterface $input, OutputInterface $output)
    {

        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManager();
        $maps = $this->managerRegistry->getRepository('MapbenderCoreBundle:Element')->findBy(array(
            'class'=>'Mapbender\CoreBundle\Element\Map',
        ));
        $output->writeln('Updating map element configs');
        $output->writeln('Found ' . count($maps) . ' map elements');
        $progressBar = new ProgressBar($output, count($maps) );
        $updatedElements = 0;
        foreach ($maps as $map) {
            /** @var Element $map */
            $config = $map->getConfiguration();
            $progressBar->advance();
            $removedConfigs = array();
            foreach ($this->getObsoleteMapOptionNames() as $obsoleteKey) {
                if (array_key_exists($obsoleteKey, $config)) {
                    unset($config[$obsoleteKey]);
                    $removedConfigs[] = $obsoleteKey;
                }
            }
            if ($removedConfigs) {
                $progressBar->setMessage("Found obsolete configuration values " . implode(', ', $removedConfigs));
                $map->setConfiguration($config);
                $em->persist($map);
                $progressBar->setMessage('Map configuration updated');
                ++$updatedElements;
            } else {
                $progressBar->setMessage('Map element already up-to-date');
            }
        }
        $em->flush();
        $progressBar->finish();
        $output->writeln('');
        if ($updatedElements) {
            $output->writeln("Updated {$updatedElements} Map elements");
        } else {
            $output->writeln("All Map elements were already up to date");
        }
    }
}

