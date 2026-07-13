<?php


namespace Mapbender\WmsBundle\EventHandler\InitDb;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\Component\Event\AbstractInitDbHandler;
use Mapbender\Component\Event\InitDbEvent;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Console\Output\OutputInterface;

class PruneInvalidWmsSourcesHandler extends AbstractInitDbHandler
{
    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function onInitDb(InitDbEvent $event): void
    {
        $output = $event->getOutput();
        $this->entityManager->beginTransaction();
        $repository = $this->entityManager->getRepository(WmsSource::class);
        $wmsSources = $repository->findAll();

        $removals = [];
        $nValid = 0;
        foreach ($wmsSources as $wmsSource) {
            /** @var WmsSource $wmsSource */
            if (!$wmsSource->getRootlayer()) {
                $output->writeln("Missing root layer in WMS #{$wmsSource->getId()} " . print_r($wmsSource->getTitle(), true), OutputInterface::VERBOSITY_VERBOSE);
                $this->entityManager->remove($wmsSource);
                ++$removals['Missing root layer'];
            } else {
                ++$nValid;
            }
        }
        if ($removals && $output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            foreach ($removals as $reason => $n) {
                $output->writeln("Removed $n WMS sources ({$reason})");
            }
        }
        $output->writeln("{$nValid} valid WMS sources kept", OutputInterface::VERBOSITY_VERY_VERBOSE);
        $this->entityManager->flush();
        $this->entityManager->commit();
    }
}
