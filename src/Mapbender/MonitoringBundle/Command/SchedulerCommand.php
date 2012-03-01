<?php

namespace Mapbender\MonitoringBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Mapbender\MonitoringBundle\Component\MonitoringRunner;
use Mapbender\Component\HTTP\HTTPClient;

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
        if(!$command = strtolower($command)){
        
            return "";
        }

        switch($command ){
            case "run":
                $sp = $this->getContainer()
                    ->get("doctrine")
                    ->getRepository('Mapbender\MonitoringBundle\Entity\SchedulerProfile')
                    ->findOneByCurrent(true);
                if($sp != null){
                    while(true){
                        $this->runCommand($input, $output);
                        sleep(10);
                    }
                }
            break;
            case "list":
                $result = $this->listCommand($input, $output);
            break;
        }

    }

    protected function listCommand(InputInterface $input, OutputInterface $output){

        $defs = $this->getContainer()
            ->get("doctrine")
            ->getRepository('Mapbender\MonitoringBundle\Entity\MonitoringDefinition')
            ->findAll();
    
        foreach($defs as $md){
            $output->writeln($md->getTitle());
        }

    }

    protected function runCommand(InputInterface $input, OutputInterface $output){

        $defs = $this->getContainer()
            ->get("doctrine")
            ->getRepository('Mapbender\MonitoringBundle\Entity\MonitoringDefinition')
            ->findAll();
    

        $em = $this->getContainer()->get("doctrine")->getEntityManager();

        foreach($defs as $md){
            $output->write($md->getTitle());
            $client = new HTTPClient($this->getContainer());
            $mr = new MonitoringRunner($md,$client);                                   
            if($md->getEnabled()){
                $job = $mr->run();                                                         
                $md->addMonitoringJob($job);                                               
                $em->persist($md);                                                         
                $em->flush();   
                $output->writeln("\t\t".$md->getLastMonitoringJob()->getStatus());
            }else{
                $output->writeln("\t\tDISABLED");
            }
        }
		
    }
}

