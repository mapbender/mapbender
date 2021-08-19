<?php


namespace Mapbender\PrintBundle\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PrintQueueGcFilesCommand extends AbstractPrintQueueCleanCommand
{
    protected function configure()
    {
        $this->setDescription("Delete unreferenced files from print queue storage path");
        $this->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('dry-run')) {
            $this->showDanglingFiles($output, $this->findDanglingFiles(), OutputInterface::VERBOSITY_QUIET);
        } else {
            $this->removeDanglingFiles($output);
        }
    }
}
