<?php


namespace Mapbender\WmsBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class UrlValidateCommand extends UrlParseCommand
{
    protected function configure()
    {
        $this
            ->setName('mapbender:wms:validate:url')
            ->setDescription('Loads, validates and parses a GetCapabilities document by url.')
            ->addArgument('serviceUrl', InputArgument::REQUIRED, 'URL to WMS')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'Username (basicauth)', '')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password (basic auth)', '')
        ;
    }

    protected function getValidationOption(InputInterface $input)
    {
        return true;
    }
}
