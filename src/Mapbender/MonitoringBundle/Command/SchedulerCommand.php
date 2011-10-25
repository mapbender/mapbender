<?php

namespace Mapbender\MonitoringBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class SchedulerCommand extends ContainerAwareCommand {
    protected function configure() {
        $this->setDefinition(array(
				new InputArgument('cmd', InputArgument::REQUIRED, 'Command (start,stop).')
            ))
            ->setHelp(<<<EOT
The <info>monitoring:scheduler</info> command starts the monitoring scheduler.

<info>./app/console/ monitoring:scheduler</info>

EOT
            )
            ->setName('monitoring:scheduler')
            ->setDescription('Starts the monitoring scheduler.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
		$command = $input->getArgument('cmd');

		$output->writeln("You want : ".$command);
		
		if(strtolower($command) === "start") {
			
		} else if(strtolower($command) === "stop") {
			
		}
    }
}

