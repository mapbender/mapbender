<?php

namespace Mapbender\MonitoringBundle\EventListener;
use Symfony\Component\EventDispatcher\Event;

class WmsListListener {

    protected $doctrine;

    public function __construct($doctrine) {
        $this->doctrine = $doctrine;
    }


    public function onWmsListLoaded(Event $event){
        $repository = $this->doctrine
            ->getRepository('Mapbender\MonitoringBundle\Entity\MonitoringDefinition');
        $data = array(); 
        $wmsIds = array();
        foreach($event->getWmsList() as $wms){
            $wmsIds[] = $wms->getId();
            $data[$wms->getId()] = "unmonitored";
        }
        $mds = $repository->findBy(array(
            "typeId" =>$wmsIds,
            "type" => get_class(new \Mapbender\WmsBundle\Entity\WMSService())
        ));
        foreach($mds as $md){
            if($lastJob = $md->getLastMonitoringJob()){
                $data[$md->getTypeId()] = $lastJob->getStatus();
            }else{
                $data[$md->getTypeId()] = "No runs yet";
            }
        } 
        $event->addColumn("status",$data);
    }
}
