<?php

namespace MB\WMSBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use MB\WMSBundle\Entity\WMSService;
use MB\WMSBundle\Entity\WMSLayer;
use MB\WMSBundle\Entity\GroupLayer;
use MB\WMSBundle\Components\CapabilitiesParser;
use MB\WMSBundle\Form\WMSType;

/*
* @package bkg
* @author Karim Malhas <karim@malhas.de>
*/
class WMSController extends Controller {
    
    /**
     * Use this function in all controllers return statements to  augment them with common data.
     * @param Array The specific data from the controller
     * @return Array The specific data from the controller augmented with the data common to all controllers,
    */
    public function commonData(array $extendedData){

        $em = $this->get("doctrine.orm.entity_manager");
        $q = $em->createQuery("select wms from MB\WMSBundle\Entity\WMSService wms ");
        $wmsArr = $q->getResult();
        $common = array(
            "menu_wmsArr" => $wmsArr
        );
        return array_merge($common,$extendedData);
    }

    /**
     * Shows the startpage of the WMS Bundle
     * @Route("/", name="mb_wms_index", requirements = { "_method" = "GET" })
     * @Template()
    */
    public function indexAction(){


        $request = $this->get('request');
        $first = $request->get('first') ? $request->get('first') : 0;
        $max = $request->get('max') ? $request->get('max') : 10;
        // allow 1000 results per page
        $max = $max < 1000 ? $max : 1000;

        $em = $this->get("doctrine.orm.entity_manager");
        $q = $em->createQuery("select wms from MB\WMSBundle\Entity\WMSService wms ");
        $q->setFirstResult($first);
        $q->setMaxResults($max);
        $wmsArr = $q->getResult();

        $nextFirst = count($wmsArr) < $max ? $first : $first + $max;
        $prevFirst = ($first - $max)  > 0 ? $first - $max : 0;
        return $this->commonData(array(
            "wmsArr" => $wmsArr,
            "nextFirst" =>  $nextFirst,
            "prevFirst" => $prevFirst,
            "max" => $max
        ));
    }


    /**
     * Shows the details of a WMS
     * @Route("/{id}", name="mb_wms_details", requirements = { "id" = "\d+","_method" = "GET" })
     * @Template()
    */
    public function detailsAction($id){
        $em = $this->get("doctrine.orm.entity_manager");
        $wms = $em->find('MBWMSBundle:WMSService',$id);
        

        if(!$wms){
            return array();
        }

        return $this->commonData(array(
            "wms" => $wms
        ));
    }
    
    /**
     * shows the dialog that allows adding a WMS
     * @Route("/add", name="mb_wms_showadd", requirements = { "_method" = "GET" })
     * @Template()
    */
    public function showaddAction(){
        return $this->commonData(array());
    }
    /**
     * adds a WMS
     * @Route("/", name="mb_wms_add", requirements = { "_method" = "POST" })
     * @Template()
    */
    public function addAction(){

        // define the structure of the data that we wish to bind from the submittet formdata
        // This does not work for arbitrarily nested WMSLayer
        $wms = new WMSService();
        $layer = new WMSLayer();
        $wms->addLayer($layer);

        $form = $this->get('form.factory')->create(new WMSType(),$wms); 
        $request = $this->get('request');
        $form->bindRequest($request);
        
    
        if($form->isValid()){
            $em = $this->get("doctrine.orm.entity_manager");
            $em->persist($wms);
            $em->flush();
            return $this->redirect($this->generateUrl("mb_wms_details",array("id" => $wms->getId()),true));
        }else{
            throw new \Exception("I am invalid");

        }
    
        
    }

    /**
     * shows the dialog for wms Deletion confirmation
     * @Route("/{id}/delete", name="mb_wms_showdelete", requirements = { "id" = "\d+","_method" = "GET" })
     * @Template()
    */
    public function showdeleteAction($id){
        return $this->commonData(array(
                'wmsTitle'=>"",
                'wmsId' => $id
        ));
    }
    /**
     * deletes a WMS
     * @Route("/{id}/delete", name="mb_wms_delete", requirements = { "id" = "\d+","_method" = "POST" })
     * @Template()
    */
    public function deleteAction($id){

        $em = $this->get("doctrine.orm.entity_manager");
        $wms = $em->find('MBWMSBundle:WMSService',$id);
        $em->remove($wms);
        $em->flush();

        return $this->commonData(array(
            "wmsId"=>$wms->getId(),
            "wmsTitle"=>$wms->getTitle()
        ));
    }

    /**
     * shows preview of WMS
     * @Route("/preview", name="mb_wms_preview", requirements = { "_method" = "POST" })
     * @Template()
    */
    public function previewAction(){
        $getcapa_url = $this->get('request')->request->get('getcapa_url');
        $data = file_get_contents($getcapa_url);
        $capaParser = new CapabilitiesParser($data);
        $wms = $capaParser->getWMSService();

        $form = $this->get('form.factory')->create(new WMSType());
        $form->setData($wms);

        return $this->commonData(array(
                "getcapa_url"=>$getcapa_url,
                "wms" => $wms,
                "form" => $form->createView(),
                "xml" => $data
            ));
    }
    


}
