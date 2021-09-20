<?php


namespace Mapbender\PrintBundle\Command;


use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;

abstract class AbstractPrintQueueCleanCommand extends AbstractPrintQueueCommand
{
    protected function findDanglingFiles()
    {
        $expectedFiles = array();
        foreach ($this->repository->findAll() as $entity) {
            $fileName = "{$this->storagePath}/{$entity->getFilename()}";
            if (file_exists($fileName)) {
                $expectedFiles[] = realpath($fileName);
            }
        }
        $foundFiles = array();
        foreach (Finder::create()->files()->in($this->storagePath) as $foundFile) {
            /** @var \SplFileInfo $foundFile */
            $foundFiles[] = $foundFile->getRealPath();
        }
        return array_diff($foundFiles, $expectedFiles);
    }

    protected function showDanglingFiles(OutputInterface $output, $names, $listVerbosity = OutputInterface::VERBOSITY_VERBOSE)
    {
        $outputVerbosity = $output->getVerbosity();
        if ($names) {
            $nFiles = count($names);
            $output->writeln("Found {$nFiles} unreferenced local files");
            foreach ($names as $name) {
                if ($outputVerbosity >= $listVerbosity && $outputVerbosity > OutputInterface::VERBOSITY_QUIET) {
                    // Prepend "bullet" in front of name above quiet mode
                    $output->write("* ");
                }
                $output->writeln("{$name}", $listVerbosity);
            }
        } else {
            $output->writeln("No unreferenced local files found");
        }
    }

    protected function removeDanglingFiles(OutputInterface $output)
    {
        $names = $this->findDanglingFiles();
        $this->showDanglingFiles($output, $names);
        if ($names) {
            $nDeleted = 0;
            foreach ($names as $name) {
                try {
                    $this->filesystem->remove($name);
                    ++$nDeleted;
                    $output->writeln("Deleted {$name}", OutputInterface::VERBOSITY_NORMAL);
                } catch (IOException $e) {
                    $output->writeln("Failed to delete {$name}: {$e->getMessage()}", OutputInterface::VERBOSITY_QUIET);
                }
            }
            $output->writeln("Deleted {$nDeleted} files total");
        }
    }
}
