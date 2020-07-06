<?php


namespace Mapbender\WmsBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Console\Output\OutputInterface;

class SourceAddCommand extends UrlParseCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('mapbender:wms:add')
        ;
    }

    protected function processSource(OutputInterface $output, WmsSource $source)
    {
        parent::processSource($output, $source);
        $em = $this->getEntityManager();
        $em->persist($source);
        $em->flush();
        $output->writeln("Saved new source #{$source->getId()}");
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        /** @var EntityManagerInterface $service */
        $service = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        return $service;
    }
}
