<?php


namespace Mapbender\CoreBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\ManagerBundle\Component\ExportHandler;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ApplicationExportCommand extends AbstractApplicationTransportCommand
{
    protected static $defaultName = 'mapbender:application:export';
    /** @var ExportHandler */
    protected $exportHandler;

    public function __construct(EntityManagerInterface $defaultEntityManager,
                                ImportHandler $importHandler,
                                ExportHandler $exportHandler,
                                ApplicationYAMLMapper $yamlRepository)
    {
        parent::__construct($defaultEntityManager, $importHandler, $yamlRepository);
        $this->exportHandler = $exportHandler;
    }

    protected function configure(): void
    {
        $this->addArgument('slug', InputArgument::REQUIRED);
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'json (default) or yaml', 'json');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        switch (strtolower($input->getOption('format'))) {
            case 'json':
                $input->setOption('format', 'json');
                break;
            case 'yml':
            case 'yaml':
                $input->setOption('format', 'yaml');
                break;
            default:
                throw new \InvalidArgumentException("Unsupported format " . print_r($input->getOption('format'), true));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $slug = $input->getArgument('slug');
        /** @var Application|null $app */
        $app = $this->getApplicationRepository()->findOneBy(array(
            'slug' => $slug,
        ));
        if (!$app) {
            $app = $this->yamlRepository->getApplication($slug);
        }
        if (!$app) {
            throw new \RuntimeException("No application with slug {$slug}");
        }

        $data = $this->exportHandler->exportApplication($app);
        unset($data['time']);
        switch ($input->getOption('format')) {
            default:
            case 'json':
                $output->writeln(json_encode($data));
                break;
            case 'yaml':
                $output->writeln(Yaml::dump($data, 20, 2));
                break;
        }
        return 0;
    }
}
