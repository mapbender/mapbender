<?php


namespace Mapbender\WmsBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class UrlValidateCommand extends UrlParseCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('mapbender:wms:validate:url')
            ->setDescription('Loads, validates and parses a GetCapabilities document by url.')
        ;
    }

    protected function getValidationOption(InputInterface $input)
    {
        return true;
    }
}
