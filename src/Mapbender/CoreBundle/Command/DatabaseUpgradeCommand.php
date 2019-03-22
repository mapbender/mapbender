<?php

namespace Mapbender\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Class DatabaseUpgradeCommand
 *
 */
class DatabaseUpgradeCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setHelp('The <info>mapbender:database:upgrade</info> command updates the Datesbase to the new schema of mapbender version 3.0.6')
            ->setName('mapbender:database:upgrade')
            ->setDescription('Updates database scheme');
    }


    /**
     * Execute command
     * @Todo Add logic to execute different action depended on the used MB3 Version
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
     * Change imagesPath configuration value from all MB3 map elements in the database
     * from  "bundles/mapbendercore/mapquery/lib/openlayers/img"
     * to "components/mapquery/lib/openlayers/img"
     */
    protected function updateMapElementConfigs(InputInterface $input, OutputInterface $output){

        /**
         * @var EntityManager $em
         * @var Element $map
         */
        $doctrine=$this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $maps = $em->getRepository('MapbenderCoreBundle:Element')->findBy(array('class'=>'Mapbender\CoreBundle\Element\Map'));
        $output->writeln('Updating map element configs');
        $output->writeln('Found ' . count($maps) . ' map elements');
        $progressBar = new ProgressBar($output, count($maps) );
        $updatedElements = 0;
        foreach ($maps as $map) {
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
        $output->writeln('Exiting now');
    }
}

