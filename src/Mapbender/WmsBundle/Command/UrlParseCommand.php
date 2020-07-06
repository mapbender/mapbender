<?php


namespace Mapbender\WmsBundle\Command;


use Mapbender\ManagerBundle\Form\Model\HttpOriginModel;
use Mapbender\WmsBundle\Component\Wms\Importer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UrlParseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mapbender:wms:parse:url')
            ->setDescription('Loads and parses a GetCapabilities document by url.')
            ->addArgument('serviceUrl', InputArgument::REQUIRED, 'URL to WMS')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'Username (basicauth)', '')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password (basic auth)', '')
            ->addOption('validate', null, InputOption::VALUE_NONE, 'Run xml schema validation (slow)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $origin = new HttpOriginModel();
        $origin->setOriginUrl($input->getArgument('serviceUrl'));
        $origin->setUsername($input->getOption('user'));
        $origin->setPassword($input->getOption('password'));

        if ($this->getValidationOption($input)) {
            $this->getImporter()->validateServer($origin);
        }

        $source = $this->loadSource($origin);

        $this->processSource($output, $source);
    }

    protected function processSource(OutputInterface $output, WmsSource $source)
    {
        $output->writeln("WMS source loaded and validated");
        $layerCount = count($source->getLayers());
        $output->writeln("Source describes $layerCount layers:");
        $this->showSource($output, $source);
    }

    protected function showSource(OutputInterface $output, WmsSource $source)
    {
        $this->showLayers($output, array($source->getRootlayer()), 1);
    }

    protected function showLayers(OutputInterface $output, $layers, $level)
    {
        $prefix = str_repeat('* ', $level);
        foreach ($layers as $layer) {
            /** @var WmsLayerSource $layer */
            $title = $layer->getTitle() ?: "<empty title>";
            $name = $layer->getName() ?: "<empty name>";
            $output->writeln("{$prefix}{$name} {$title}");
            $this->showLayers($output, $layer->getSublayer(), $level + 1);
        }
    }

    /**
     * @param HttpOriginModel $origin
     * @return WmsSource
     */
    protected function loadSource(HttpOriginModel $origin)
    {
        /** @var WmsSource $source */
        $source = $this->getImporter()->evaluateServer($origin)->getSource();
        return $source;
    }

    /**
     * @return Importer
     */
    protected function getImporter()
    {
        /** @var Importer $importer */
        $importer = $this->getContainer()->get('mapbender.importer.source.wms.service');
        return $importer;
    }

    protected function getValidationOption(InputInterface $input)
    {
        return $input->getOption('validate');
    }
}
