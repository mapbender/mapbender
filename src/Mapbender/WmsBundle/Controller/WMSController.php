<?php

namespace Mapbender\WmsBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\WmsBundle\Entity\WMSService;
use Mapbender\WmsBundle\Entity\WMSLayer;
use Mapbender\WmsBundle\Entity\GroupLayer;
use Mapbender\WmsBundle\Component\CapabilitiesParser;
use Mapbender\WmsBundle\Form\WMSType;
use Mapbender\Component\HTTP\HTTPClient;
use Mapbender\WmsBundle\Event\WmsIndexEvent;
use Mapbender\WmsBundle\WmsEvents;

/*
* @package bkg
* @author Karim Malhas <karim@malhas.de>
*/
class WMSController extends Controller {
    
    /**
     * Shows the startpage of the WMS Bundle
     * @Route("/")
     * @Method({"GET"})
     * @Template()
     * @ParamConverter("wmsList",class="Mapbender\WmsBundle\Entity\WMSService")
    */
    public function indexAction(array $wmsList){

        $total = $this->getDoctrine()
            ->getEntityManager()
            ->createQuery("SELECT count(w.id) as total From MapbenderWmsBundle:WmsService w")
            ->getScalarResult();
        // Grrr   why can't php allow ()[] ?
        $total = $total[0]['total'];
        $request = $this->get('request');
        $offset = $request->get('usedOffset');
        $limit = $request->get('usedLimit');
        $nextOffset = count($wmsList) < $limit ? $offset : $offset + $limit;
        $prevOffset = ($offset - $limit)  > 0 ? $offset - $limit : 0;
        $lastOffset = ($total - $limit)  > 0 ? $total - $limit : 0;

        $dispatcher = $this->get("event_dispatcher");
    
        $wmsListLoadedEvent = new WmsIndexEvent();
        $wmsListLoadedEvent->setWmsList($wmsList);
        $dispatcher->dispatch(WmsEvents::onWmsIndex, $wmsListLoadedEvent);
        $mdefs = $this->getDoctrine()->getEntityManager()
                ->getRepository("MapbenderMonitoringBundle:MonitoringDefinition")
                ->findAll();
        $defined_mds = array();
        if ($mdefs !== null){
            foreach ($mdefs as $md) {
                $defined_mds[$md->getTypeId()] = true;
            }
        }
        return array(
            "wmsList" => $wmsList,
            "offset" => $offset,
            "nextOffset" =>  $nextOffset,
            "prevOffset" => $prevOffset,
            "lastOffset" => $lastOffset,
            "limit" => $limit,
            "total" => $total,
            "defined_mds" => $defined_mds,
            "extraColumns" => $wmsListLoadedEvent->getColumns(),
        );
    }


    /**
     * shows the dialog that allows adding a WMS
     * @Route("/add")
     * @Method({"GET"})
     * @Template()
    */
    public function registerAction(){
        return array( 'getcapa_url'=>'');
    }
    
    /*
     * Make sure the url is correct,. add missing parameters and filter sessionids
    */ 
    protected function CapabilitiesURLFixup($url){
        $sessionids = array(
          "PHPSESSID",
          "jsessionid"
        );
        $parsedUrl = HTTPClient::parseUrl($url);
        $parsedQuery = HTTPClient::parseQueryString($parsedUrl['query']);

        $resultQuery = array();
        foreach($parsedQuery as $key => $value){
          if(!in_array($key,$sessionids)){
            $resultQuery[$key] = $value;
          }
        } 
    
        $parsedUrl['query'] = HTTPClient::buildQueryString($resultQuery);
        return HTTPClient::buildUrl($parsedUrl);
    }
  
    /**
     * shows preview of WMS
     * @Route("/preview")
     * @Method({"POST"})
     * @Template()
    */
    public function previewAction(){
        $getcapa_url = $this->get('request')->request->get('getcapa_url');

        $getcapa_url = $this->CapabilitiesURLFixup($getcapa_url);

        $user = $this->get('request')->request->get('http_user');
        $password = $this->get('request')->request->get('http_password');
        if(!$getcapa_url){
            $this->get('session')->setFlash('error', "url not set");
            return $this->render("MapbenderWmsBundle:WMS:register.html.twig",array("getcapa_url",$getcapa_url));
        }
        try {
            $client = new HTTPClient($this->container);
            if($user){
              $client->setUsername($user);
              $client->setPassword($password);
            } 
            $result = $client->open(trim($getcapa_url));

            if($result->getStatusCode() == 200){
                if(!$result->getData()){
                    $this->get("logger")->debug("$getcapa_url returned no data");
                    throw new \Exception("Preview: Service '$getcapa_url' returned no Data");
                }
                $capaParser = new CapabilitiesParser($result->getData());
                $wms = $capaParser->getWMSService();
                if(!$wms){
                    $this->get("logger")->debug("Could not parse data for url '$getcapa_url'");
                    throw new \Exception("Preview: Could not parse data for url '$getcapa_url'");
                }
            }else{
                throw new \Exception("Preview: Server said '".$result->getStatusCode() . " ". $result->getStatusMessage(). "'");
            }
        }catch(\Exception $E){
            $this->get('session')->setFlash('error',$E->getMessage());
            return $this->render("MapbenderWmsBundle:WMS:register.html.twig",array("getcapa_url" => $getcapa_url));
        }
        
        $wmsWithSameTitle  =$this->getDoctrine()
            ->getEntityManager()
            ->getRepository("MapbenderWmsBundle:WMSService")
            ->findByTitle($wms->getTitle());

        if ( count($wmsWithSameTitle) > 0) {
            $wms->setAlias(count($wmsWithSameTitle));
        } 
    

        $wms->setUsername($user);
        $wms->setPassword($password);
        // Save these Formats somewhere somehow
        $form = $this->get('form.factory')->create(new WMSType(), $wms,array(
            "exceptionFormats" => $wms->getAllExceptionFormats(),
            "requestGetCapabilitiesFormats" => $wms->getRequestGetCapabilitiesFormats(),
            "requestGetMapFormats" => $wms->getRequestGetMapFormats(),
            "requestGetFeatureInfoFormats" => $wms->getRequestGetFeatureInfoFormats(),
            "requestDescribeLayerFormats"  => $wms->getRequestDescribeLayerFormats(),
            "requestGetLegendGraphicFormats" => $wms->getRequestGetLegendGraphicFormats(),
            "requestGetStylesFormats" => $wms->getRequestGetStylesFormats(),
            "requestPutStylesFormats" => $wms->getRequestPutStylesFormats(),
        ));
        
        return array(
                "getcapa_url"=>$getcapa_url,
                "wms" => $wms,
                "form" => $form->createView(),
            );
    }

    /**
     * adds a WMS
     * @Route("/")
     * @Method({"POST"})
    */
    public function addAction(){

        $request = $this->get('request');

        $requestWMS = $request->get('WMSService');
        $wms = new WMSService();
        $wms = $this->buildWMSFormStructure($wms,$requestWMS);


        // wms has basic structure... but at this point we don't know what it supports
        // for multiselect to work we need to know what it supports..
        $form = $this->get('form.factory')->create(new WMSType(),$wms,array(
            "exceptionFormats" => isset($requestWMS['exceptionFormats'])?$requestWMS['exceptionFormats']:array(),
            "requestGetCapabilitiesFormats" => isset($requestWMS['requestGetCapabilitiesFormats'])?$requestWMS['requestGetCapabilitiesFormats']:array(),
            "requestGetMapFormats" => isset($requestWMS['requestGetMapFormats'])?$requestWMS['requestGetMapFormats']:array(),
            "requestGetFeatureInfoFormats" => isset($requestWMS['requestGetFeatureInfoFormats'])?$requestWMS['requestGetFeatureInfoFormats']:array(),
            "requestDescribeLayerFormats"  => isset($requestWMS['requestDescribeLayerFormats'])?$requestWMS['requestDescribeLayerFormats']:array(),
            "requestGetLegendGraphicFormats" => isset($requestWMS['requestGetLegendGraphicFormats'])?$requestWMS['requestGetLegendGraphicFormats']:array(),
            "requestGetStylesFormats" => isset($requestWMS['requestGetStylesFormats'])?$requestWMS['requestGetStylesFormats']:array(),
            "requestPutStylesFormats" => isset($requestWMS['requestPutStylesFormats'])?$requestWMS['requestPutStylesFormats']:array(),
        )); 
        $form->bindRequest($request);

        $wms->setSupportedExceptionFormats(
            isset($requestWMS['exceptionFormats'])
            ? $requestWMS['exceptionFormats']
            :array());

        $wms->setRequestSupportedGetCapabilitiesFormats(
            isset($requestWMS['requestGetCapabilitiesFormats'])
            ? $requestWMS['requestGetCapabilitiesFormats']
            :array());
        
        $wms->setRequestSupportedGetMapFormats(
            isset($requestWMS['requestGetMapFormats'])
            ? $requestWMS['requestGetMapFormats']
            : array());

        $wms->setRequestSupportedGetFeatureInfoFormats(
            isset($requestWMS['requestGetFeatureInfoFormats'])
            ? $requestWMS['requestGetFeatureInfoFormats']
            : array());

        $wms->setRequestSupportedDescribeLayerFormats(
            isset($requestWMS['requestDescribeLayerFormats'])
            ? $requestWMS['requestDescribeLayerFormats']
            : array());

        $wms->setRequestSupportedGetLegendGraphicFormats(
            isset($requestWMS['requestGetLegendGraphicFormats'])
            ? $requestWMS['requestGetLegendGraphicFormats']
            : array());

         $wms->setRequestSupportedGetStylesFormats(
            isset($requestWMS['requestGetStylesFormats'])
            ? $requestWMS['requestGetStylesFormats']
            : array());
         
         $wms->setRequestSupportedPutStylesFormats(
            isset($requestWMS['requestPutStylesFormats'])
            ? $requestWMS['requestPutStylesFormats']
            : array());


    
        if($form->isValid()){
            $em = $this->get("doctrine.orm.entity_manager");
            $this->persistRecursive($wms,$em);
            $em->persist($wms);
            $em->flush();
            $this->get('session')->setFlash('info',"WMS Added");
            return $this->redirect($this->generateUrl("mapbender_wms_wms_index",array(), true));
        }else{
            // FIXME: getcapa_url is missing, xml is missing
            $this->get('session')->setFlash('error',"Could not Add WMS");
            return $this->render("MapbenderWmsBundle:WMS:preview.html.twig",array(
                    "getcapa_url"=> "",
                    "wms" => $wms,
                    "form" => $form->createView(),
                    "xml" =>""
                ));
        }
    
        
    }

    /**
     * Shows the WMS in an Editor
     * @Route("/{wmsId}")
     * @Method({"GET"})
     * @Template()
    */
    public function editAction(WMSService $wms){
        $form = $this->get('form.factory')->create(new WMSType(),$wms,array(
            "exceptionFormats" => $wms->getExceptionFormats(),
            "requestGetCapabilitiesFormats" => $wms->getRequestSupportedGetCapabilitiesFormats(),
            "requestGetMapFormats" => $wms->getRequestSupportedGetMapFormats(),
            "requestGetFeatureInfoFormats" => $wms->getRequestSupportedGetFeatureInfoFormats(),
            "requestDescribeLayerFormats"  => $wms->getRequestSupportedDescribeLayerFormats(),
            "requestGetLegendGraphicFormats" => $wms->getRequestSupportedGetLegendGraphicFormats(),
            "requestGetStylesFormats" => $wms->getRequestSupportedGetStylesFormats(),
            "requestPutStylesFormats" => $wms->getRequestSupportedPutStylesFormats(),
        )); 
        return array(
            "wms" => $wms,
            "form"  => $form->createView(),
        );
    }
    
    /**
     * Shows the WMS in an Editor
     * @Route("/{wmsId}")
     * @Method({"POST"})
     * @Template()
    */
    public function saveAction(WMSService $wms){
        $request = $this->get('request');
        /* build up nested wmslayer structure */
        $requestWMS = $request->get('WMSService'); 



        /*

            Attention!:
        
            The Point of the @$arr[idex]?:array()-Mumbo is to resize the array in
            the form so that when this method is called from updateAction, there
            are no mismatches between slosts noi the form an what is submitted
            in the *Formats arrays.
            I am placing a FIXME here, because that seems kind of klunky

        */
    
        $wms->setSupportedExceptionFormats(@$requestWMS['exceptionFormats']?:array());
        $wms->setRequestSupportedGetCapabilitiesFormats(@$requestWMS['requestGetCapabilitiesFormats']?:array());
        $wms->setRequestSupportedGetMapFormats(@$requestWMS['requestGetMapFormats']?:array());
        $wms->setRequestSupportedGetFeatureInfoFormats(@$requestWMS['requestGetFeatureInfoFormats']?:array());
        $wms->setRequestSupportedDescribeLayerFormats(@$requestWMS['requestDescribeLayerFormats ']?:array());
        $wms->setRequestSupportedGetLegendGraphicFormats(@$requestWMS['requestGetLegendGraphicFormats']?:array());
        $wms->setRequestSupportedGetStylesFormats(@$requestWMS['requestGetStylesFormats']?:array());
        $wms->setRequestSupportedPutStylesFormats(@$requestWMS['requestPutStylesFormats']?:array());

        $form = $this->get('form.factory')->create(new WMSType(),$wms,array(
            "exceptionFormats" => $wms->getSupportedExceptionFormats(),
            "requestGetCapabilitiesFormats" => $wms->getRequestSupportedGetCapabilitiesFormats(),
            "requestGetMapFormats" => $wms->getRequestSupportedGetMapFormats(),
            "requestGetFeatureInfoFormats" => $wms->getRequestSupportedGetFeatureInfoFormats(),
            "requestDescribeLayerFormats"  => $wms->getRequestSupportedDescribeLayerFormats(),
            "requestGetLegendGraphicFormats" => $wms->getRequestSupportedGetLegendGraphicFormats(),
            "requestGetStylesFormats" => $wms->getRequestSupportedGetStylesFormats(),
            "requestPutStylesFormats" => $wms->getRequestSupportedPutStylesFormats(),
        )); 
        $form->bindRequest($request);
        if($form->isValid()){
            $em = $this->get("doctrine.orm.entity_manager");
            $this->persistRecursive($wms,$em);
            $this->get('session')->setFlash('info',"WMS Saved");
            return $this->redirect($this->generateUrl("mapbender_wms_wms_edit", array("wmsId"=>$wms->getId())));
        }else{
            $this->get('session')->setFlash('error',"Could not Save WMS");
            return $this->render("MapbenderWmsBundle:WMS:edit.html.twig",array(
                    "wms" => $wms,
                    "form" => $form->createView(),
                ));
        }


    }

    /**
     *  Show two WMS in an editor, the current, and the new
     *  @Route("/{wmsId}/update")
     *  @Method({"GET"})
     *  @Template()
    */

    public function updateAction(WMSService $wms){
        // FIXME: this url buidling thing is brittle!
        $serviceUrl = $wms->getRequestGetCapabilitiesGET();
        $version = $wms->getVersion();

        if (strstr($serviceUrl,"?")){
            $concat = "&";
        }else{
            $concat = "?";
        }

        $url = $serviceUrl .$concat."VERSION=".urlencode($version)."&REQUEST=GetCapabilities&SERVICE=wms";
        // FIXME: move this kludge into WMSService
        $newWms = null;
        try {
            $client = new HTTPClient($container=$this->container);
            $result = $client->open(trim($url));

            if($result->getStatusCode() == 200){
                if(!$result->getData()){
                    $this->get("logger")->debug("$url returned no data");
                    throw new \Exception("Update: Service '$url' returned no Data");
                }
                $capaParser = new CapabilitiesParser($result->getData());
                $newWms = $capaParser->getWMSService();
                if(!$newWms){
                    $this->get("logger")->debug("Could not parse data for url '$url'");
                    throw new \Exception("Update: Could not parse data for url '$url'");
                }
            }else{
                throw new \Exception("Update: Server said '".$result->getStatusCode() . " ". $result->getStatusMessage(). "'");
            }
        }catch(\Exception $E){
            $this->get('session')->setFlash('error', $E->getMessage());
            $this->get('session')->setFlash('error',"tried to load WMS from '$url'");
            return $this->redirect($this->generateUrl("mapbender_wms_wms_edit",array("wmsId"=>$wms->getId())));
        }

        $form = $this->get('form.factory')->create(new WMSType(),$wms,array(
            "exceptionFormats" => $wms->getExceptionFormats(),
            "requestGetCapabilitiesFormats" => $wms->getRequestGetCapabilitiesFormats(),
            "requestGetMapFormats" => $wms->getRequestGetMapFormats(),
            "requestGetFeatureInfoFormats" => $wms->getRequestGetFeatureInfoFormats(),
            "requestDescribeLayerFormats"  => $wms->getRequestDescribeLayerFormats(),
            "requestGetLegendGraphicFormats" => $wms->getRequestGetLegendGraphicFormats(),
            "requestGetStylesFormats" => $wms->getRequestGetStylesFormats(),
            "requestPutStylesFormats" => $wms->getRequestPutStylesFormats(),
        )); 
        $newForm = $this->get('form.factory')->create(new WMSType(),$newWms,array(
            "exceptionFormats" => $newWms->getExceptionFormats(),
            "requestGetCapabilitiesFormats" => $newWms->getRequestGetCapabilitiesFormats(),
            "requestGetMapFormats" => $newWms->getRequestGetMapFormats(),
            "requestGetFeatureInfoFormats" => $newWms->getRequestGetFeatureInfoFormats(),
            "requestDescribeLayerFormats"  => $newWms->getRequestDescribeLayerFormats(),
            "requestGetLegendGraphicFormats" => $newWms->getRequestGetLegendGraphicFormats(),
            "requestGetStylesFormats" => $newWms->getRequestGetStylesFormats(),
            "requestPutStylesFormats" => $newWms->getRequestPutStylesFormats(),
        )); 
        return array(
            "url" => trim($url),
            "wms" => $wms,
            "form"  => $form->createView(),
            "newWms" => $newWms,
            "newForm"  => $newForm->createView(),
        );
        
    }

    /**
     * shows the dialog for wms Deletion confirmation
     * @Route("/{wmsId}/delete")
     * @Method({"GET"})
     * @Template()
    */
    public function confirmdeleteAction(WMSService $wms){
        return array(
               'wms' => $wms 
        );
    }
    
    /**
     * deletes a WMS
     * @Route("/{wmsId}/deletecomponents")
     * @Method({"POST"})
    */
    public function deletecomponentsAction(WMSService $wms){
        // TODO: check wether a layer is used by a VWMS still
        try{
            $request = $this->get("request");
            $request->setMethod('POST');
            $request->setAttribute("wmsId", $wms->getId());
            $response = $this->forward(
                    "MapbenderMonitoringBundle:MonitoringDefinitionController:fromwmsdeleteAction",
                    array("wmsId" => $wms->getId()));
            
            return $response;
        }catch(\Exception $e){
            $em = $this->getDoctrine()->getEntityManager();
            $this->removeRecursive($wms,$em);
            $em->remove($wms);
            $em->flush();
            //FIXME: error handling
            $this->get('session')->setFlash('info',"WMS deleted");
            return $this->redirect($this->generateUrl("mapbender_wms_wms_index"));
        }
        
    }

    /**
     * deletes a WMS
     * @Route("/{wmsId}/delete")
     * @Method({"POST"})
    */
    public function deleteAction(WMSService $wms){
        // TODO: check wether a layer is used by a VWMS still
        $em = $this->getDoctrine()->getEntityManager();
        $this->removeRecursive($wms,$em);
        $em->remove($wms);
        $em->flush();
        $this->get('session')->setFlash('info',"WMS deleted");
        if($wms->getId() === null){
            try{ // remove monitoringdefinition if exists
                $md = $this->getDoctrine()->getRepository(
                        "MapbenderMonitoringBundle:MonitoringDefinition")
                        ->findOneBy(array(
                            "type"=>get_class($wms), "typeId" => $wms->getId()));
                $response = $this->forward(
                        "MapbenderMonitoringBundle:MonitoringDefinition:fromwmsdelete",
                        array("mdId" => $md->getId()));
            }catch(\Exception $e){
                
            }
        }
        //FIXME: error handling
        return $this->redirect($this->generateUrl("mapbender_wms_wms_index"));
    }

 
    /**
     * Recursively persists a nested Layerstructure
     * param GroupLayer
     * param EntityManager
    */
    public function persistRecursive($grouplayer,$em){
        $em->persist($grouplayer);
        if(count($grouplayer->getLayer()) > 0 ){
            foreach($grouplayer->getLayer() as $layer){
                $layer->setParent($grouplayer);
                $this->persistRecursive($layer,$em);
            }
        }
        $em->flush();
    }

    /**
     * Recursively remove a nested Layerstructure
     * param GroupLayer
     * param EntityManager
    */
    public function removeRecursive($grouplayer,$em){
        foreach($grouplayer->getLayer() as $layer){
            $this->removeRecursive($layer,$em);
        }
        $em->flush();
        $em->remove($grouplayer);
    }

    /**
     *  
     * Takes an Arraystructure from a POSTrequest and recurses into the nested layers to build a matching WMSLayer structure
     * So that a Form can be bound to the layer
     * param GroupLayer the rootlayer of the Layer hierarchy
     * param array POST request from a WMS structure
    */
    public function buildWMSFormStructure($grouplayer,array $grouplayerArr){
        if(isset($grouplayerArr['layer']) && is_array($grouplayerArr['layer'])){
                foreach($grouplayerArr['layer'] as $layerArr){
                        $layer = new WMSLayer();
                        if(isset($layerArr['layer']) && is_array($layerArr['layer']) && count($layerArr['layer'])){
                                $layer = $this->buildWMSFormStructure($layer, $layerArr);

                        }   
                        $grouplayer->addLayer($layer);
                }   
        }   
        return $grouplayer;
    } 

}
