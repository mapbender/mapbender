<?php


namespace Mapbender\WmsBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Component\Wms\Importer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractSourceCommand extends Command
{
    protected ManagerRegistry $managerRegistry;
    protected Importer $importer;

    public function __construct(ManagerRegistry $managerRegistry,
                                Importer $importer)
    {
        parent::__construct();
        $this->managerRegistry = $managerRegistry;
        $this->importer = $importer;
    }

    protected function getImporter(): Importer
    {
        return $this->importer;
    }

    /**
     * @throws \LogicException
     */
    protected function getSourceById(string|int $id): WmsSource
    {
        /** @var WmsSource|null $source */
        $source = $this->getEntityManager()->getRepository(Source::class)->find($id);
        if (!$source) {
            throw new \LogicException("No source with id $id");
        }
        return $source;
    }

    protected function showSource(OutputInterface $output, WmsSource $source): void
    {
        $layerCount = count($source->getLayers());
        $output->writeln("Source #{$source->getId()} describes $layerCount layers (origin url: {$source->getOriginUrl()}):");
        $this->showLayers($output, array($source->getRootlayer()), 1);
    }

    protected function showLayers(OutputInterface $output, $layers, $level): void
    {
        $prefix = str_repeat('* ', $level);
        foreach ($layers as $layer) {
            /** @var WmsLayerSource $layer */
            $title = $layer->getTitle() ?: "<empty title>";
            $name = $layer->getName() ?: "<empty name>";
            $output->writeln("$prefix$name $title");
            $this->showLayers($output, $layer->getSublayer(), $level + 1);
        }
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->managerRegistry->getManager();
    }
}
