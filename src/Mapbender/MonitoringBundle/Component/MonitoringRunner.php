<?php
namespace Mapbender\MonitoringBundle\Component;
use Mapbender\MonitoringBundle\Entity\MonitoringDefinition;
use Mapbender\MonitoringBundle\Entity\MonitoringJob;
use Mapbender\Component\HTTP\HTTPClient;

class MonitoringRunner {
    protected $md;
    protected $client;

    public function __construct(MonitoringDefinition $md,HTTPclient $client){
        $this->md = $md;
        $this->client = $client;
    }

    public function run(){
        $job = new MonitoringJob();
        $time_pre = microtime(true);
        $result = null;
        try {
            $result = $this->client->open($this->md->getRequestUrl());
            $job->setResult($result->getData());
            if($result->getStatusCode()=="200") {
                $teststr = substr($result->getData(), 0, 100);
                if(strripos(strtolower($teststr), "exception") !== false){
                    $job->setSTATUS(MonitoringJob::$STATUS_EXCEPTION);
                } else {
                    $job->setSTATUS(MonitoringJob::$STATUS_SUCCESS);
                }
            } else 
                $job->setSTATUS(MonitoringJob::$STATUS_ERROR.":".$result->getStatusCode());
        }catch(\Exception $E){
            $job->setSTATUS(MonitoringJob::$STATUS_FAIL);
        }
        $time_post = microtime(true);
        $job->setMonitoringDefinition($this->md);
        $job->setLatency(round($time_post-$time_pre,3));
        return $job;
    }
}
