<?php


namespace Mapbender\WmsBundle\Command;

use Mapbender\WmsBundle\Entity\WmsOrigin;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mapbender\WmsBundle\Component\Wms\Importer;


class ServiceValidateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mapbender:services:validate:wms')
            ->setDescription('Replace host name in configured WMS / WFS services.')
            ->addArgument('serviceUrl', InputArgument::REQUIRED, 'URL to WMS')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'Username (basicauth)', '')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password (basic auth)', '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $origin = new WmsOrigin($input->getArgument('serviceUrl'), $input->getOption('user'), $input->getOption('password'));

        $importer = new Importer($this->getContainer());
        $result = $importer->evaluateServer($origin, true);
        $wmsSource = $result->getWmsSourceEntity();
    }
}
