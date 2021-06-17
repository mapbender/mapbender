<?php


namespace Mapbender\WmsBundle\Command;


use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command mapbender:wms:add
 */
class SourceAddCommand extends UrlParseCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('mapbender:wms:add')
            ->setDescription('Adds a new WMS source')
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
}
