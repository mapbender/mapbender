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

use Mapbender\MonitoringBundle\Entity\SchedulerProfile;

class SchedulerCommand extends ContainerAwareCommand {
    protected function configure() {
        $this->setDefinition(array(
            new InputArgument('cmd', InputArgument::REQUIRED, 'Command (start,stop).'),
            new InputArgument('caller', InputArgument::OPTIONAL, 'Caller()')
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
        $output->writeln("\t\tEXEC");
		$command = $input->getArgument('cmd');
        if(!$command = strtolower($command)){
        
            return "";
        }

        switch($command ){
            case "run":
                $run = true;
                $num = 0;
                while($run){
                    $num ++;
                    $sp = $this->getContainer()
                            ->get("doctrine")
                            ->getRepository('Mapbender\MonitoringBundle\Entity\SchedulerProfile')
                            ->findOneByCurrent(true);
                    if($sp != null){
                        $em = $this->getContainer()
                            ->get("doctrine")->getEntityManager();
                        $timestamp_act = time();
//                        if($sp->canStart()) { // check
                            $hour_sec = 3600;
                            $sleepbeforstart = 0;
                            $starttimeinterval = $sp->getStarttimeinterval();
                            if($starttimeinterval < $hour_sec * 24) {
//                                    $sleepbeforstart = 0;
                                $lastendtime = $sp->getLastendtime();
                                $laststarttime = $sp->getLaststarttime();
                                if($laststarttime == null) {
                                    $sleepbeforstart = 0;
                                    $timestamp_start = $timestamp_act;
                                } else {
                                    $timestamp_laststart = date_timestamp_get($laststarttime);
                                    $sleepbeforstart = $timestamp_act - $timestamp_laststart;
                                    if($sleepbeforstart > $starttimeinterval) {
                                        $sleepbeforstart = 0;
                                        $timestamp_start = $timestamp_act;
                                    } else {
                                        $timestamp_start = $timestamp_laststart + $starttimeinterval;
                                        $sleepbeforstart = $timestamp_start - $timestamp_act;
                                    }
                                }

                            } else {
                                $starttime = $sp->getStarttime();
                                $time = date("H:i",date_timestamp_get($starttime));
                                $timestamp_start = date_timestamp_get(new \DateTime($time));
                                if($timestamp_start < $timestamp_act){ // start next day
                                    $timestamp_start += $hour_sec * 24;
                                }
                                $sleepbeforstart = $timestamp_start - $timestamp_act;
                                $sp->setNextstarttime(new \DateTime(date("Y-m-d H:i",$timestamp_start)));
                                $sp->setStatusWaitstart();
                                $em->persist($sp);
                                $em->flush();
                            }
                            // sleep
                            sleep($sleepbeforstart);
                            $sp->setLaststarttime(new \DateTime(date("Y-m-d H:i",$timestamp_start)));
//                            $sp->setNextstarttime(null);
                            $sp->setNextstarttime(new \DateTime(date("Y-m-d H:i", time() + $sp->getStarttimeinterval())));
                            $this->runCommandII($input, $output, $sp);
                    } else {
                        // $sp null
//                         $run = false;
                    }
                    sleep(10);
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
    
    protected function runCommandII(InputInterface $input, OutputInterface $output, SchedulerProfile $sp){

        $defs = $this->getContainer()
            ->get("doctrine")
            ->getRepository('Mapbender\MonitoringBundle\Entity\MonitoringDefinition')
            ->findAll();
    
        $em = $this->getContainer()->get("doctrine")->getEntityManager();
        

        if(count($defs)==0){
            $output->writeln("\t\t NO JOB FOUND");
            $sp->setLastendtime(new \DateTime(date("Y-m-d H:i",$timestamp_start)));
            $sp->setStatusError();
            $em->persist($sp);
            $em->flush();
            return false;
        } else {
            $output->writeln("\t\t JOBS RUN");
            $sp->setLastendtime(null);
            foreach($defs as $md){
                $sp->setStatusRunning();
                $em->persist($sp);
                $em->flush();
                $output->write($md->getTitle());
                $client = new HTTPClient($this->getContainer());
                $mr = new MonitoringRunner($md, $client);                                   
                if($md->getEnabled()){
                    $job = $mr->run();                                                         
                    $md->addMonitoringJob($job);                                               
                    $em->persist($md);                                                         
                    $em->flush();   
                    $output->writeln("\t\t".$md->getLastMonitoringJob()->getStatus());
                }else{
                    $output->writeln("\t\tDISABLED");
                }
                $sp->setStatusWaitjobstart();
//                $sp->setNextstarttime(new \DateTime(date("Y-m-d H:i", time() + $sp->getJobinterval())));
                $em->persist($sp);
                $em->flush();
                sleep($sp->getJobinterval());
            }
            $sp->setLastendtime(new \DateTime(date("Y-m-d H:i", time())));
            $sp->setStatusEnded();
            $em->persist($sp);
            $em->flush();
        }
    }

}

