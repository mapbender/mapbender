<?php


namespace Mapbender\PrintBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PrintQueueCleanCommand extends AbstractPrintQueueCleanCommand
{

    protected function configure()
    {
        $this
            ->setDescription("Purge old jobs from the print queue (database + files)")
            ->addArgument('age', InputArgument::OPTIONAL, "Cutoff age in days", 20)
            ->addOption('remove-dangling-files', 'gc', InputOption::VALUE_NONE, "Delete locally found but unreferenced files")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $minDays = intval($input->getArgument('age'));
        if ($minDays < 0) {
            throw new \InvalidArgumentException("Invalid age argument");
        }
        $output->writeln('Print queue clean process started.');
        $countDeleted = 0;
        $cutoffDate = new \DateTime("-{$minDays} days");
        foreach ($this->repository->findOlderThan($cutoffDate) as $entity) {
            $pdfPath = $this->getJobStoragePath($entity);
            if ($this->filesystem->exists($pdfPath)) {
                $output->writeln("Deleting file {$pdfPath} from job #{$entity->getId()}");
                // NOTE: this will throw if deletion fails (privileges issues etc)
                $this->filesystem->remove($pdfPath);
            } else {
                $output->writeln("File {$pdfPath} from job #{$entity->getId()} is missing");
            }
            $output->writeln("Deleting database row for job #{$entity->getId()}");
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
            ++$countDeleted;
        }
        $output->writeln("Deleted {$countDeleted} print queue item(s)");
        if ($input->getOption('remove-dangling-files')) {
            $this->removeDanglingFiles($output);
        }
    }
}
