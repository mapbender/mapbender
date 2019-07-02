<?php


namespace Mapbender\CoreBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApplicationImportCommand extends AbstractApplicationTransportCommand
{
    protected function configure()
    {
        $this->setName('mapbender:application:import');
        $this->addArgument('input', InputArgument::REQUIRED, 'File name (`-` for stdin)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $root = $this->getRootUser();
        $fileName = $input->getArgument('input');
        if ($fileName === '-') {
            $content = file_get_contents('php://stdin');
        } else {
            $content = file_get_contents($fileName);
        }

        $importHandler = $this->getApplicationImporter();
        $importArray = $importHandler->parseImportData($content);
        $em = $this->getDefaultEntityManager();
        $em->beginTransaction();
        try {
            $applications = $importHandler->importApplicationData($importArray);
            if ($root) {
                foreach ($applications as $application) {
                    $importHandler->setDefaultAcls($application, $root);
                }
            } else {
                $output->writeln("WARNING: root user not found, no owner will be assigned to imported applications", OutputInterface::VERBOSITY_QUIET);
            }
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
        $output->writeln("Imported " . count($applications) . " applications", OutputInterface::VERBOSITY_NORMAL);
        foreach ($applications as $application) {
            $output->writeln("* {$application->getSlug()}", OutputInterface::VERBOSITY_NORMAL);
        }
    }
}
