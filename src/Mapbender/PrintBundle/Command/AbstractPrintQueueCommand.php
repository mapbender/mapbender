<?php


namespace Mapbender\PrintBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\PrintBundle\Component\Service\PrintServiceInterface;
use Mapbender\PrintBundle\Entity\QueuedPrintJob;
use Mapbender\PrintBundle\Repository\QueuedPrintJobRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractPrintQueueCommand extends Command
{
    /** @var Filesystem */
    protected $filesystem;
    /** @var PrintServiceInterface */
    protected $printService;
    /** @var EntityManagerInterface */
    protected $entityManager;
    /** @var QueuedPrintJobRepository */
    protected $repository;
    /** @var string */
    protected $storagePath;

    public function __construct(RegistryInterface $managerRegistry,
                                Filesystem $filesystem,
                                $storagePath)
    {
        $this->repository = $managerRegistry->getRepository('MapbenderPrintBundle:QueuedPrintJob');
        $this->entityManager = $managerRegistry->getManager();
        $this->filesystem = $filesystem;
        $this->storagePath = $storagePath;
        parent::__construct(null);
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
