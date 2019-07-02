<?php


namespace Mapbender\WmsBundle\Command;

use Mapbender\ManagerBundle\Form\Model\HttpOriginModel;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mapbender\WmsBundle\Component\Wms\Importer;


class UrlValidateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mapbender:wms:validate:url')
            ->setDescription('Replace host name in configured WMS / WFS services.')
            ->addArgument('serviceUrl', InputArgument::REQUIRED, 'URL to WMS')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'Username (basicauth)', '')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password (basic auth)', '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $origin = new HttpOriginModel();
        $origin->setOriginUrl($input->getArgument('serviceUrl'));
        $origin->setUsername($input->getOption('user'));
        $origin->setPassword($input->getOption('password'));

        /** @var Importer $importer */
        $importer = $this->getContainer()->get('mapbender.importer.source.wms.service');
        $result = $importer->evaluateServer($origin, true);
        $wmsSource = $result->getWmsSourceEntity();

        $output->writeln("WMS source loaded and validated");
        $layers = $wmsSource->getLayers();
        $layerCount = count($layers);
        $output->writeln("Source describes $layerCount layers:");
        foreach ($layers as $layer) {
            $output->writeln("* {$layer->getTitle()}");
        }
    }
}
