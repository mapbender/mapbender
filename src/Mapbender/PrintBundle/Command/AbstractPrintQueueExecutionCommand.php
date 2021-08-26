<?php


namespace Mapbender\PrintBundle\Command;


use Doctrine\Persistence\ManagerRegistry;
use Mapbender\PrintBundle\Component\Service\PrintServiceInterface;
use Mapbender\PrintBundle\Entity\QueuedPrintJob;
use Mapbender\Utils\MemoryUtil;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractPrintQueueExecutionCommand extends AbstractPrintQueueCommand
{
    /** @var PrintServiceInterface */
    protected $printService;

    protected $memoryLimit;

    public function __construct(ManagerRegistry $managerRegistry,
                                Filesystem $filesystem,
                                PrintServiceInterface $printService,
                                $storagePath,
                                $memoryLimit)
    {
        parent::__construct($managerRegistry, $filesystem, $storagePath);
        $this->printService = $printService;
        $this->memoryLimit = $memoryLimit;
    }

    protected function beforePrint()
    {
        MemoryUtil::increaseMemoryLimit($this->memoryLimit);
    }

    /**
     * @param OutputInterface $output
     * @param QueuedPrintJob $job
     */
    protected function runJob(OutputInterface $output, $job)
    {
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
            if ($this->filesystem->exists($outputPath)) {
                $output->writeln("Assuming job has been canceled. Deleting just created pdf at {$outputPath}");
                $this->filesystem->remove($outputPath);
            }
        } else {
            $this->entityManager->persist($job);
            $job->setCreated(new \DateTime());
            $this->entityManager->flush();

            $output->writeln("PDF for queued job #{$job->getId()} rendered to {$outputPath}");
        }
    }
}
