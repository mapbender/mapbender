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
            if($result->getStatusCode()=="200") {
                if(is_int(strpos($result->getHeader('Content-Type'), "image/"))){ # check if contentType image
                    $job->setResult(base64_encode($result->getData()));
                    $job->setSTATUS(MonitoringJob::$STATUS_SUCCESS);
                } else {
                    $job->setResult($result->getData());
                    $isXml = true;
                    $xml = new \DOMDocument();
                    if(!$xml->loadXML($result->getData())){
                        if(!$xml->loadHTML($xmlDocStr)){
                              $isXml = false;
                        }
                    }
                    if($isXml){
                        if(strripos(strtolower($xml->documentElement->tagName), "exception") !== false){
                            $job->setSTATUS(MonitoringJob::$STATUS_EXCEPTION);
                        } else {
                            $job->setSTATUS(MonitoringJob::$STATUS_SUCCESS);
                        }
                    } else {
                        $job->setSTATUS(MonitoringJob::$STATUS_SUCCESS);
                    }
                }
            } else {
                $job->setResult($result->getData());
                $job->setSTATUS(MonitoringJob::$STATUS_ERROR.":".$result->getStatusCode());
            }
        }catch(\Exception $E){
            $job->setSTATUS(MonitoringJob::$STATUS_FAIL);
        }
        $time_post = microtime(true);
        $job->setMonitoringDefinition($this->md);
        $job->setLatency(round($time_post-$time_pre,3));
        return $job;
    }
}
