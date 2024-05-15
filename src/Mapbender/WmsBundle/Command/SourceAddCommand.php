<?php


namespace Mapbender\WmsBundle\Command;


use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command mapbender:wms:add
 */
#[AsCommand('mapbender:wms:add')]
class SourceAddCommand extends UrlParseCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Adds a new WMS source')
            ->addOption(self::OPTION_DEACTIVATE_NEW_LAYERS, null, InputOption::VALUE_NONE, 'If set, newly added layers will be deactivated in existing instances. Deactivated layers are not visible in the frontend.')
            ->addOption(self::OPTION_DESELECT_NEW_LAYERS, null, InputOption::VALUE_NONE, 'If set, newly added layers will be deselected in existing instances. Deselected layers are not visible on the map by default, but appear in the layer tree and can be selected by users.')
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
