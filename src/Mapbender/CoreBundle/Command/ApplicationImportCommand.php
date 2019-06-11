<?php


namespace Mapbender\CoreBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Entity\User;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApplicationImportCommand extends ContainerAwareCommand
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

    /**
     * @return User|null
     */
    protected function getRootUser()
    {
        $repository = $this->getDefaultEntityManager()->getRepository('FOMUserBundle:User');
        foreach ($repository->findAll() as $user) {
            /** @var User $user*/
            if ($user->isAdmin()) {
                return $user;
            }
        }
        return null;
    }

    protected function getDefaultEntityManager()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        return $em;
    }

    /**
     * @return ImportHandler
     */
    protected function getApplicationImporter()
    {
        /** @var ImportHandler $service */
        $service = $this->getContainer()->get('mapbender.application_importer.service');
        return $service;
    }
}
