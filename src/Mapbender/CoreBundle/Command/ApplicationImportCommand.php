<?php


namespace Mapbender\CoreBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\DependencyInjection\Compiler\MapbenderYamlCompilerPass;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

class ApplicationImportCommand extends AbstractApplicationTransportCommand
{
    /** @var boolean */
    protected $strictElementConfigs;

    public function __construct(EntityManagerInterface $defaultEntityManager,
                                ImportHandler $importHandler,
                                ApplicationYAMLMapper $yamlRepository,
                                $strictElementConfigs)
    {
        parent::__construct($defaultEntityManager, $importHandler, $yamlRepository);
        $this->strictElementConfigs = $strictElementConfigs;
    }

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
            $content = \file_get_contents('php://stdin');
            $fileData = $this->importHandler->parseImportData($content);
            $this->processExportData($fileData, $input, $output);
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
                    $this->processFile($path, $input, $output);
                }
            } else {
                throw new \InvalidArgumentException("Input path {$inputArg} does not exist");
            }
        }
    }

    protected function processFile($path, InputInterface $input, OutputStyle $output)
    {
        $content = \file_get_contents($path);
        $fileData = $this->importHandler->parseImportData($content);
        // Detect Yaml application definition
        if (!empty($fileData['parameters']['applications'])) {
            $appConfigs = $fileData['parameters']['applications'];
            $this->processYamlDefinitions($appConfigs, $path, $input, $output);
        } else {
            $this->processExportData($fileData, $input, $output);
        }
    }

    /**
     * @param mixed[] $exportData
     * @param InputInterface $input
     * @param OutputStyle $output
     * @throws \Mapbender\ManagerBundle\Component\Exception\ImportException
     */
    protected function processExportData($exportData, InputInterface $input, OutputStyle $output)
    {
        $em = $this->getDefaultEntityManager();
        $em->beginTransaction();
        try {
            $applications = $this->importHandler->importApplicationData($exportData);
            foreach ($applications as $application) {
                $this->addRootOwnership($application, $output);
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

    protected function addRootOwnership(Application $application, OutputStyle $output)
    {
        $root = $this->getRootUser();
        if ($root) {
            $rootSid = UserSecurityIdentity::fromAccount($root);
            $this->importHandler->addOwner($application, $rootSid);
        } else {
            $output->writeln("WARNING: root user not found, no owner will be assigned to imported application {$application->getSlug()}", OutputInterface::VERBOSITY_QUIET);
        }
    }

    protected function processYamlDefinitions($appConfigs, $fileName, InputInterface $input, OutputStyle $output)
    {
        $em = $this->getDefaultEntityManager();
        // HACK: use public methods on Compiler pass class to preprocess definition
        $yamlCompiler = new MapbenderYamlCompilerPass(null);
        $yamlCompiler->setStrictElementConfigs($this->strictElementConfigs);
        foreach ($appConfigs as $slug => $rawAppConfig) {
            $appConfig = $yamlCompiler->prepareApplicationConfig($rawAppConfig, $slug, $fileName);
            $tempApplication = $this->yamlRepository->createApplication($appConfig);
            $tempApplication->setId($slug);
            $tempApplication->setSlug($slug);

            $newSlug = EntityUtil::getUniqueValue($em, get_class($tempApplication), 'slug', $tempApplication->getSlug() . '_yml', '');
            $newTitle = EntityUtil::getUniqueValue($em, get_class($tempApplication), 'title', $tempApplication->getTitle(), ' ');
            $em->beginTransaction();
            try {
                $application = $this->importHandler->duplicateApplication($tempApplication, $newSlug);
                $application->setTitle($newTitle);
                $this->addRootOwnership($application, $output);
                $em->commit();
                $output->writeln("Imported new Application {$application->getSlug()} from {$fileName}", OutputInterface::VERBOSITY_NORMAL);
            } catch (\Exception $e) {
                $em->rollback();
                throw $e;
            }
        }
    }
}
