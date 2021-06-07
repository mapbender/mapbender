<?php


namespace Mapbender\CoreBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

class ApplicationImportCommand extends AbstractApplicationTransportCommand
{
    protected function configure()
    {
        $this->setName('mapbender:application:import');
        $this->addArgument('input', InputArgument::REQUIRED, 'File name (`-` for stdin) or directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);
        $inputArg = $input->getArgument('input');
        if ($inputArg === '-') {
            $this->importOne(file_get_contents('php://stdin'), $input, $output);
        } else {
            if (@\file_exists($inputArg)) {
                if (\is_dir($inputArg)) {
                    $inputPaths = array();
                    foreach (\scandir($inputArg) as $file) {
                        if (preg_match('#\.(yml|yaml)$#', $file)) {
                            $inputPaths[] = realpath($inputArg) . '/' . $file;
                        }
                    }
                    if (!$inputPaths) {
                        $output->warning("Input directory {$inputArg} is empty");
                    }
                } else {
                    $inputPaths = array($inputArg);
                }
                foreach ($inputPaths as $path) {
                    // @todo Sf4: support yaml application definition directly (to replace fixture-based mechanism)
                    $this->importOne(\file_get_contents($path), $input, $output);
                }
            } else {
                throw new \InvalidArgumentException("Input path {$inputArg} does not exist");
            }
        }
    }

    /**
     * @param string $content
     * @param InputInterface $input
     * @param OutputStyle $output
     * @throws \Mapbender\ManagerBundle\Component\Exception\ImportException
     */
    protected function importOne($content, InputInterface $input, OutputStyle $output)
    {
        $root = $this->getRootUser();
        $importHandler = $this->getApplicationImporter();
        $importArray = $importHandler->parseImportData($content);
        $em = $this->getDefaultEntityManager();
        $em->beginTransaction();
        try {
            $applications = $importHandler->importApplicationData($importArray);
            if ($root) {
                $rootSid = UserSecurityIdentity::fromAccount($root);
                foreach ($applications as $application) {
                    $importHandler->addOwner($application, $rootSid);
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
