<?php


namespace Mapbender\WmsBundle\Command;


use Mapbender\WmsBundle\Component\Wms\Importer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractSourceCommand extends Command
{
    /** @var RegistryInterface */
    protected $managerRegistry;
    /** @var Importer */
    protected $importer;

    public function __construct(RegistryInterface $managerRegistry,
                                Importer $importer)
    {
        parent::__construct(null);
        $this->managerRegistry = $managerRegistry;
        $this->importer = $importer;
    }

    /**
     * @return Importer
     */
    protected function getImporter()
    {
        return $this->importer;
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

    protected function getEntityManager()
    {
        return $this->managerRegistry->getEntityManager(null);
    }
}
