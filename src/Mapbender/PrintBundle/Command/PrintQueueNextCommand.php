<?php

namespace Mapbender\PrintBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrintQueueNextCommand extends AbstractPrintQueueCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription("Run queued print jobs")
            ->setName('mapbender:print:queue:next')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkHung($output);
        $this->processNext($output);
        $unprocessedCount = count($this->repository->findReadyForProcessing());
        $output->writeln("{$unprocessedCount} unprocessed jobs remaining", OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * @param OutputInterface $output
     */
    protected function checkHung(OutputInterface $output)
    {
        foreach ($this->repository->findHung() as $hungJob) {
            $output->writeln("[WARNING]: Job #{$hungJob->getId()} is not successfully rendered yet. Run this command with --repair option to retry");
        }
    }

    /**
     * @param OutputInterface $output
     * @param int $limit
     */
    protected function processNext(OutputInterface $output, $limit = 1)
    {
        $readyJobs = $this->repository->findReadyForProcessing();
        if ($readyJobs) {
            foreach (array_values($readyJobs) as $i => $openJob) {
                if ($i >= $limit) {
                    break;
                }
                $this->runJob($output, $openJob);
            }
        } else {
            $output->writeln("The print queue is empty", OutputInterface::VERBOSITY_VERBOSE);
        }
    }
}
