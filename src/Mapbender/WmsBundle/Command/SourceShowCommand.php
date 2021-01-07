<?php


namespace Mapbender\WmsBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SourceShowCommand extends AbstractSourceCommand
{
    protected function configure()
    {
        $this
            ->setName('mapbender:wms:show')
            ->setDescription('Displays layer information of a persisted WMS source')
            ->addArgument('id', InputArgument::REQUIRED, 'Id of the source')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $this->getSourceById($input->getArgument('id'));
        $this->showSource($output, $source);
    }
}
