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
                $sp_start = $this->getContainer()->get("doctrine")
                            ->getRepository('Mapbender\MonitoringBundle\Entity\SchedulerProfile')
                            ->findOneByCurrent(true);
                if($sp_start != null){
                    $sp_start->setLaststarttime(null);
                    $sp_start->setLastendtime(null);
                    $sp_start->setNextstarttime(null);
                    $this->getContainer()->get("doctrine")
                            ->getEntityManager()->persist($sp_start);
                    $this->getContainer()->get("doctrine")
                            ->getEntityManager()->flush();
                }
                
                while($run){
                    $num ++;
                    $sp = $this->getContainer()->get("doctrine")
                            ->getRepository('Mapbender\MonitoringBundle\Entity\SchedulerProfile')
                            ->findOneByCurrent(true);
                    if($sp != null && $sp_start->getId() == $sp->getId()){
                        $now =  new \DateTime(date("Y-m-d H:i:s", time()));
                            $hour_sec = 3600;
                            $sleepbeforestart = 0;
                            if($sp->getNextstarttime() === null) { // first time
                                if($sp->getStarttime() > $now){
                                    $sleepbeforestart = date_timestamp_get(
                                            $sp->getStarttime())
                                            - date_timestamp_get($now);
                                    $sp->setNextstarttime(
                                            new \DateTime(date("Y-m-d H:i:s",
                                                    date_timestamp_get(
                                                            $sp->getStarttime()))));
                                } else {
                                    $sleepbeforestart = $hour_sec * 24 - (
                                            date_timestamp_get($now)
                                            - date_timestamp_get(
                                                    $sp->getStarttime()));
                                    $sp->setNextstarttime(
                                            new \DateTime(date("Y-m-d H:i:s",
                                                    date_timestamp_get($now)
                                                    + $sleepbeforestart)));
                                }
                            } else {
                                if($sp->getNextstarttime() <= $now){
                                    $nextstarttime_stamp =  date_timestamp_get(
                                            $sp->getNextstarttime());
                                    $now_stamp =  date_timestamp_get($now);
                                    while($nextstarttime_stamp < $now_stamp){
                                        $nextstarttime_stamp += $sp->getStarttimeinterval();
                                    }
                                    $sp->setNextstarttime(null);
                                    $sp->setNextstarttime(
                                            new \DateTime(date("Y-m-d H:i:s",
                                                    $nextstarttime_stamp)));
                                }
                                $sleepbeforestart = date_timestamp_get(
                                        $sp->getNextstarttime()) - date_timestamp_get($now);
                            }
                            $sp->setStatusWaitstart();
                            $this->getContainer()->get("doctrine")
                                    ->getEntityManager()->persist($sp);
                            $this->getContainer()->get("doctrine")
                                    ->getEntityManager()->flush();
                            // sleep
                            sleep($sleepbeforestart);
                            $sp->setLaststarttime($sp->getNextstarttime());
                            $sp->setNextstarttime(null);
                            $sp->setNextstarttime(
                                    new \DateTime(date("Y-m-d H:i:s",
                                    date_timestamp_get($sp->getLaststarttime())
                                            + $sp->getStarttimeinterval())));
                            $this->getContainer()->get("doctrine")
                                    ->getEntityManager()->persist($sp);
                            $this->getContainer()->get("doctrine")
                                    ->getEntityManager()->flush();
                            
                            $this->runCommandII($input, $output, $sp);
                            
                            $sp->setLastendtime(
                                    new \DateTime(date("Y-m-d H:i:s", time())));
                            $sp->setStatusEnded();
                            $this->getContainer()->get("doctrine")
                                    ->getEntityManager()->persist($sp);
                            $this->getContainer()->get("doctrine")
                                    ->getEntityManager()->flush();
                    } else {
                        $run = false;
                    }
                    if($sp->getStarttimeinterval() == 0){
                        $run = false;
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
            $now = new \DateTime();
            $output->write($now." ".$md->getTitle());
            $client = new HTTPClient($this->getContainer());
            $mr = new MonitoringRunner($md,$client);                                   
            if($md->getEnabled()){
                $time_from = $this->md->getRuleStart();
                $time_end = $this->md->getRuleEnd();
                if($time_from == $time_end
                        || ($now > $time_from && $now < $time_end)){
                    $job = $mr->run();                                                         
                    $md->addMonitoringJob($job);                                               
                    $em->persist($md);                                                         
                    $em->flush();
                    $output->writeln("\t\t".$md->getLastMonitoringJob()->getStatus());
                } else {
                    $output->writeln("\t\tEXCEPT TIME");
                }
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
            return false;
        } else {
            $output->writeln("\t\t JOBS RUN:".count($defs));
            foreach($defs as $md){
                $sp->setStatusRunning();
                $em->persist($sp);
                $em->flush();
                $output->write($md->getTitle());
                $client = new HTTPClient($this->getContainer());
                $mr = new MonitoringRunner($md, $client);
                if($md->getEnabled()){
                    if($md->getRuleMonitor()){
                        $now = new \DateTime();
                        if($now > $md->getRuleStart() && $now < $md->getRuleEnd()){
                            $job = $mr->run();                                                         
                            $md->addMonitoringJob($job);                                               
                            $em->persist($md);                                                         
                            $em->flush();   
                            $output->writeln("\t\t".$md->getLastMonitoringJob()->getStatus());
                        } else {
                            $output->writeln("\t\tEXCEPT TIME");
                        }
                    } else {
                        $job = $mr->run();                                                         
                        $md->addMonitoringJob($job);                                               
                        $em->persist($md);                                                         
                        $em->flush();   
                        $output->writeln("\t\t".$md->getLastMonitoringJob()->getStatus());
                    }
                }else{
                    $output->writeln("\t\tDISABLED");
                }
                $sp->setStatusWaitjobstart();
                $em->persist($sp);
                $em->flush();
                sleep($sp->getJobinterval());
            }
        }
    }

}

