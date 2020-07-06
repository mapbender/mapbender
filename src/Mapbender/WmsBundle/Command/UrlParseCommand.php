<?php


namespace Mapbender\WmsBundle\Command;


use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UrlParseCommand extends AbstractHttpCapabilitiesProcessingCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('mapbender:wms:parse:url')
            ->setDescription('Loads and parses a GetCapabilities document by url.')
        ;
    }

    protected function processSource(OutputInterface $output, WmsSource $source)
    {
        $this->showSource($output, $source);
    }

    protected function getValidationOption(InputInterface $input)
    {
        return $input->getOption('validate');
    }
}
