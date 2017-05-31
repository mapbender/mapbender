<?php

namespace Mapbender\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DatabaseUpgradeCommand
 *
 */
class DatabaseUpgradeCommand extends ContainerAwareCommand {
    private $generator;

    protected function getGenerator() {
        if($this->generator === null) {
            $this->generator = new ElementGenerator();
        }
        return $this->generator;
    }
    protected function configure() {
        $this
            ->setHelp('The <info>mapbender:database:upgrade</info> command updates the Datesbase to the new schema of mapbender version 3.0.6')
            ->setName('mapbender:database:upgrade')
            ->setDescription('Updates database scheme');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->changeMapsImagePath();
    }

    protected function changeMapsImagePath(){

        /**
         * @var EntityManager $em
         * @var Element $map
         */
        $doctrine=$this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $maps = $em->getRepository('MapbenderCoreBundle:Element')->findBy(array('class'=>'Mapbender\CoreBundle\Element\Map'));
        foreach ($maps as $map){
            $config = $map->getConfiguration();
            /*
            * old imgPath: bundles/mapbendercore/mapquery/lib/openlayers/img
            * new imgPath: components/mapquery/lib/openlayers/img
            */
            if($config['imgPath'] == "bundles/mapbendercore/mapquery/lib/openlayers/img"){
                $config['imgPath']="components/mapquery/lib/openlayers/img";
                $map->setConfiguration($config);
                $em->persist($map);
            }
        }
        $em->flush();
    }
}

