<?php


namespace Mapbender\PrintBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mapbender\PrintBundle\Entity\QueuedPrintJob;
use Mapbender\PrintBundle\Repository\QueuedPrintJobRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractPrintQueueCommand extends Command
{
    /** @var ManagerRegistry */
    protected $managerRegistry;
    /** @var Filesystem */
    protected $filesystem;
    /** @var EntityManagerInterface */
    protected $entityManager;
    /** @var QueuedPrintJobRepository */
    protected $repository;
    /** @var string */
    protected $storagePath;

    public function __construct(ManagerRegistry $managerRegistry,
                                Filesystem $filesystem,
                                $storagePath)
    {
        $this->managerRegistry = $managerRegistry;
        $this->filesystem = $filesystem;
        $this->storagePath = $storagePath;
        parent::__construct(null);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->repository = $this->managerRegistry->getRepository('MapbenderPrintBundle:QueuedPrintJob');
        $this->entityManager = $this->managerRegistry->getManager();
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
