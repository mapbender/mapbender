<?php


namespace Mapbender\WmsBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\WmsBundle\Component\Wms\Importer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractSourceCommand extends ContainerAwareCommand
{
    /**
     * @return Importer
     */
    protected function getImporter()
    {
        /** @var Importer $importer */
        $importer = $this->getContainer()->get('mapbender.importer.source.wms.service');
        return $importer;
    }

    /**
     * @param string $id
     * @return WmsSource
     */
    protected function getSourceById($id)
    {
        /** @var WmsSource|null $source */
        $source = $this->getEntityManager()->getRepository('Mapbender\CoreBundle\Entity\Source')->find($id);
        if (!$source) {
            throw new \LogicException("No source with id {$id}");
        }
        return $source;
    }

    protected function showSource(OutputInterface $output, WmsSource $source)
    {
        $layerCount = count($source->getLayers());
        $output->writeln("Source describes $layerCount layers:");
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
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        /** @var EntityManagerInterface $service */
        $service = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        return $service;
    }
}
