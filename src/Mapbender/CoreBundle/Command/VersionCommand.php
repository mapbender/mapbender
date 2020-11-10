<?php


namespace Mapbender\CoreBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class VersionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mapbender:version')
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
            $name = $this->getContainer()->getParameter('branding.project_name');
            $version = $this->getContainer()->getParameter('branding.project_version');
        } else {
            $name = $this->getContainer()->getParameter('mapbender.branding.name');
            $version = $this->getContainer()->getParameter('mapbender.version');
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
