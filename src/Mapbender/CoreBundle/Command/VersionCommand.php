<?php


namespace Mapbender\CoreBundle\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class VersionCommand extends Command
{
    protected $name;
    protected $version;
    protected $projectName;
    protected $projectVersion;

    public function __construct($name, $version, $projectName, $projectVersion)
    {
        $this->name = $name;
        $this->version = $version;
        $this->projectName = $projectName;
        $this->projectVersion = $projectVersion;
        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setHelp('Display Mapbender version')
            // --version is taken by Symfony...
            ->addOption('--number-only', null, InputOption::VALUE_NONE, 'Display only version (default: name and version)')
            ->addOption('--name-only', null, InputOption::VALUE_NONE, 'Display only name (default: name and version)')
            ->addOption('--project', null, InputOption::VALUE_NONE, 'Display project [name and] version instead of Mapbender [name and] version')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        if ($input->getOption('name-only') && $input->getOption('number-only')) {
            throw new \InvalidArgumentException("Can't combine --name-only and --number-only");
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($input->getOption('project')) {
            $name = $this->projectName;
            $version = $this->projectVersion;
        } else {
            $name = $this->name;
            $version = $this->version;
        }
        if ($input->getOption('name-only')) {
            $output->writeln($name);
        } elseif ($input->getOption('number-only')) {
            $output->writeln($version);
        } else {
            $output->writeln("{$name} {$version}");
        }
    }
}
