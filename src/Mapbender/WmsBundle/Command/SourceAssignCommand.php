<?php


namespace Mapbender\WmsBundle\Command;


use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\ManagerBundle\Controller\SourceInstanceController;
use Mapbender\WmsBundle\Component\Wms\Importer;
use Mapbender\WmsBundle\Entity\WmsSource;
use setasign\Fpdi\PdfParser\CrossReference\ReaderInterface;
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
    public function __construct(private SourceInstanceController $sourceInstanceController, ManagerRegistry $managerRegistry, Importer $importer)
    {
        parent::__construct($managerRegistry, $importer);
    }

    public const ARGUMENT_APPLICATION = "application";
    public const ARGUMENT_SOURCE = "source";

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('mapbender:wms:assign')
            ->setDescription('Assigns a WMS source to an application')
            ->addArgument(self::ARGUMENT_APPLICATION, InputArgument::REQUIRED, "id or slug of the application")
            ->addArgument(self::ARGUMENT_SOURCE, InputArgument::REQUIRED, "id of the wms source")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

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
            return Command::FAILURE;
        }

        $layersets = $this->getEntityManager()->getRepository(Layerset::class)->findBy(['application' => $application]);
        var_dump($layersets);

        return Command::SUCCESS;
        $sourceId = $input->getArgument(self::ARGUMENT_SOURCE);
        $this->sourceInstanceController->createNewSourceInstance($application, $sourceId, $layersetId, $this->getEntityManager());
        return Command::SUCCESS;
    }


}
