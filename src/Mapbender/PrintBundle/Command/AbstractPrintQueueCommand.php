<?php


namespace Mapbender\PrintBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\PrintBundle\Component\Service\PrintServiceInterface;
use Mapbender\PrintBundle\Entity\QueuedPrintJob;
use Mapbender\PrintBundle\Repository\QueuedPrintJobRepository;
use Mapbender\Utils\MemoryUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractPrintQueueCommand extends ContainerAwareCommand
{
    /** @var PrintServiceInterface */
    protected $printService;
    /** @var EntityManagerInterface */
    protected $entityManager;
    /** @var QueuedPrintJobRepository */
    protected $repository;
    /** @var string */
    protected $storagePath;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->printService = $this->getContainer()->get('mapbender.print_service_bridge.service');
        $this->entityManager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->repository = $this->entityManager->getRepository('MapbenderPrintBundle:QueuedPrintJob');
        $this->storagePath = $this->getContainer()->getParameter('mapbender.print.queue.storage_dir');
        parent::initialize($input, $output);
    }

    protected function beforePrint()
    {
        $memoryLimitParam = $this->getContainer()->getParameter('mapbender.print.queue.memory_limit');
        MemoryUtil::increaseMemoryLimit($memoryLimitParam);
    }

    /**
     * @param OutputInterface $output
     * @param QueuedPrintJob $job
     */
    protected function runJob(OutputInterface $output, $job)
    {
        /** @var Filesystem $fs */
        $fs = $this->getContainer()->get('filesystem');
        $output->writeln("Starting processing of queued job #{$job->getId()}");
        $this->entityManager->persist($job);
        $job->setStarted(new \DateTime());
        $job->setCreated(null);
        $this->entityManager->flush();

        $outputPath = $this->getJobStoragePath($job);
        $this->beforePrint();
        $this->printService->storePrint($job->getPayload(), $outputPath);
        if (!$this->repository->findOneBy(array('id' => $job->getId()))) {
            $output->writeln("WARNING: after print execution, entity #{$job->getId()} can no longer be found");
            if ($fs->exists($outputPath)) {
                $output->writeln("Assuming job has been canceled. Deleting just created pdf at {$outputPath}");
                $fs->remove($outputPath);
            }
        } else {
            $this->entityManager->persist($job);
            $job->setCreated(new \DateTime());
            $this->entityManager->flush();

            $output->writeln("PDF for queued job #{$job->getId()} rendered to {$outputPath}");
        }
    }

    /**
     * @param QueuedPrintJob $job
     * @return string
     */
    protected function getJobStoragePath(QueuedPrintJob $job)
    {
        return "{$this->storagePath}/{$job->getFilename()}";
    }
}
