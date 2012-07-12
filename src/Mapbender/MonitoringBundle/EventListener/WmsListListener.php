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
        if( count($wmsIds) < 1){
            return;
        }
        $mds = $repository->findBy(array(
            "typeId" =>$wmsIds,
            "type" => get_class(new \Mapbender\WmsBundle\Entity\WMSService())
        ));
        foreach($mds as $md){
            if($lastJob = $md->getLastMonitoringJob()){
                if ($lastJob->getStatus() == "SUCCESS"){
                    $data[$md->getTypeId()] = 
                        '<span class="monitoring success">Everything is fine</span>';
                }else{
                    $data[$md->getTypeId()] = 
                        '<span class="monitoring failure">Something is broken</span>';

                }
            }else{
                $data[$md->getTypeId()] = '<span class="monitoring success">No runs yet</span>';
            }
        } 
        $event->addColumn("status",$data);
    }
}
