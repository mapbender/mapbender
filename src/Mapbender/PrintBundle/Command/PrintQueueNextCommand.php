<?php

namespace Mapbender\PrintBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PrintQueueNextCommand extends AbstractPrintQueueExecutionCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription("Run queued print jobs")
            ->addOption('max-jobs', null, InputOption::VALUE_REQUIRED,
                        'Limit to processing <n> maximum jobs before exiting (default 1; use 0 for unlimited)', 1)
            ->addOption('max-time', null, InputOption::VALUE_REQUIRED,
                        'Run for at most <n> seconds, waiting for / processing jobs, before exiting (default 30; use 0 for unlimited)', 30)
            ->addOption('poll-interval', null, InputOption::VALUE_REQUIRED,
                        'Poll every <n> seconds when waiting for jobs (default 1; fractions supported)', 1)
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkHung($output);
        $maxJobs = intval($input->getOption('max-jobs')) ?: null;
        $maxTime = floatval($input->getOption('max-time')) ?: null;
        $pollInterval = max(0.125, floatval($input->getOption('poll-interval')));

        $t0 = microtime(true);
        $jobsProcessed = 0;
        do {
            $t0poll = microtime(true);
            $processed = $this->processNext($output);
            $tNow = microtime(true);
            if ($processed) {
                $jobsProcessed += 1;
            } else {
                $sleepSeconds = $pollInterval - max(0.0, $tNow - $t0poll);
                $output->writeln("Waiting {$sleepSeconds} seconds for next job", OutputInterface::VERBOSITY_VERY_VERBOSE);
                usleep(intval(1000000 * $sleepSeconds));
                $tNow = microtime(true);
            }
            $processMoreJobs = ($maxJobs === null) || ($maxJobs > $jobsProcessed);
            $waitMore = ($maxTime === null) || ($maxTime > ($tNow - $t0));
        } while ($waitMore && $processMoreJobs);
        $unprocessedCount = count($this->repository->findReadyForProcessing());
        $output->writeln("{$unprocessedCount} unprocessed jobs remaining", OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * @param OutputInterface $output
     */
    protected function checkHung(OutputInterface $output)
    {
        foreach ($this->repository->findHung() as $hungJob) {
            $output->writeln("[WARNING]: Job #{$hungJob->getId()} has not finished rendering yet. Use mapbender:print:queue:repair if you think this is an error.");
        }
    }

    /**
     * @param OutputInterface $output
     * @param int $limit
     * @return boolean to indicate that a pending job has been found and processed
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
                return true;
            }
        } else {
            $output->writeln("The print queue is empty", OutputInterface::VERBOSITY_VERBOSE);
            return false;
        }
    }
}
