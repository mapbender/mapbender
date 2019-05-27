<?php


namespace Mapbender\CoreBundle\Command;


use Doctrine\ORM\EntityManager;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\ManagerBundle\Component\ExportHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ApplicationExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('mapbender:application:export');
        $this->addArgument('slug', InputArgument::REQUIRED);
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'json (default) or yml', 'json');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        switch (strtolower($input->getOption('format'))) {
            case 'json':
                $input->setOption('format', 'json');
                break;
            case 'yml':
            case 'yaml':
                $input->setOption('format', 'yml');
                break;
            default:
                throw new \InvalidArgumentException("Unsupported format " . print_r($input->getOption('format'), true));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Application|null $app */
        $app = $this->getRepository()->findOneBy(array(
            'slug' => $input->getArgument('slug'),
        ));
        $exporter = $this->getExporter();
        $data = $exporter->exportApplication($app);
        unset($data['time']);
        switch ($input->getOption('format')) {
            default:
            case 'json':
                $output->writeln(json_encode($data));
                break;
            case 'yml':
                $output->writeln(Yaml::dump($data, 20, 2));
                break;
        }
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository()
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        return $em->getRepository('MapbenderCoreBundle:Application');
    }

    /**
     * @return ExportHandler
     */
    protected function getExporter()
    {
        /** @var ExportHandler $exporter */
        $exporter = $this->getContainer()->get('mapbender.application_exporter.service');
        return $exporter;
    }
}
