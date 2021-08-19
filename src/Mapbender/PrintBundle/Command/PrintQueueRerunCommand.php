<?php


namespace Mapbender\PrintBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrintQueueRerunCommand extends AbstractPrintQueueExecutionCommand
{
    protected function configure()
    {
        $this
            ->setDescription("Rerun a print queue job")
            ->addArgument('id', InputArgument::REQUIRED, "id of job (required)")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = intval($input->getArgument('id'));
        $job = $this->repository->find($id);
        if ($job) {
            $this->runJob($output, $job);
        } else {
            throw new \RuntimeException("No queued print job with id $id");
        }
    }
}
