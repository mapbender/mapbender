<?php


namespace Mapbender\WmsBundle\Command;


use Doctrine\Persistence\ManagerRegistry;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\ManagerBundle\Controller\SourceInstanceController;
use Mapbender\WmsBundle\Component\Wms\Importer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command mapbender:wms:assign
 */
class SourceAssignCommand extends AbstractSourceCommand
{
    protected static $defaultName = 'mapbender:wms:assign';
    public function __construct(private SourceInstanceController $sourceInstanceController, ManagerRegistry $managerRegistry, Importer $importer)
    {
        parent::__construct($managerRegistry, $importer);
    }

    public const ARGUMENT_APPLICATION = "application";
    public const ARGUMENT_SOURCE = "source";
    public const ARGUMENT_LAYERSET = "layerset";

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Assigns a WMS source to an application')
            ->addArgument(self::ARGUMENT_APPLICATION, InputArgument::REQUIRED, "id or slug of the application")
            ->addArgument(self::ARGUMENT_SOURCE, InputArgument::REQUIRED, "id of the wms source")
            ->addArgument(self::ARGUMENT_LAYERSET, InputArgument::OPTIONAL, "id or name of the layerset. Defaults to 'main' or the first layerset in the application.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $application = $this->findApplication($input, $io);
        if (!$application) return Command::FAILURE;

        $layerset = $this->findLayerset($input, $io, $application);
        if (!$layerset) return Command::FAILURE;

        $sourceId = $input->getArgument(self::ARGUMENT_SOURCE);

        $this->sourceInstanceController->createNewSourceInstance($application, $sourceId, $layerset->getId(), $this->getEntityManager());
        $io->success("New source instance added.");
        return Command::SUCCESS;
    }

    private function findApplication(InputInterface $input, SymfonyStyle $io): ?Application
    {
        $applicationIdOrSlug = $input->getArgument(self::ARGUMENT_APPLICATION);
        $criteria = [];
        if (is_numeric($applicationIdOrSlug)) {
            $criteria['id'] = intval($applicationIdOrSlug);
        } else {
            $criteria['slug'] = $applicationIdOrSlug;
        }
        $application = $this->getEntityManager()->getRepository(Application::class)->findOneBy($criteria);

        if (!$application) {
            $io->error("Could not find application $applicationIdOrSlug");
            return null;
        }
        return $application;
    }

    private function findLayerset(InputInterface $input, SymfonyStyle $io, Application $application): ?Layerset
    {
        $layersetIdOrSlug = $input->getArgument(self::ARGUMENT_LAYERSET);
        $layersets = $this->getEntityManager()->getRepository(Layerset::class)->findBy(['application' => $application]);

        if ($layersetIdOrSlug) {
            $layersetFiltered = array_filter($layersets, fn(Layerset $l) => $l->getId() == $layersetIdOrSlug || $l->getTitle() == $layersetIdOrSlug);
            if (count($layersetFiltered) < 1) {
                $io->error("Could not find layerset $layersetIdOrSlug in application {$application->getTitle()}");
                return null;
            }
            return reset($layersetFiltered);
        }

        $mainLayerset = array_filter($layersets, fn(Layerset $l) => $l->getTitle() == 'main');
        return count($mainLayerset) > 0 ? reset($mainLayerset) : reset($layersets);
    }


}
