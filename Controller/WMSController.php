<?php

namespace MB\WMSBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class WMSController extends Controller {

    /**
     * Shows the startpage of the WMS Bundle
    */
    public function indexAction(){
        return $this->render("MBWMSBundle:WMS:index.html.twig");
    }

    /**
     * Shows the details of a WMS
    */
    public function detailsAction($wmsId){
        $wmsTitle = "Title for $wmsId";
        return $this->render("MBWMSBundle:WMS:details.html.twig",array('wmsTitle'=>$wmsTitle));
    }
    
    /**
     * shows the dialog that allows adding a WMS
    */
    public function showaddAction(){
        return $this->render("MBWMSBundle:WMS:showadd.html.twig");
    }

    /**
     * shows preview of WMS
    */
    public function previewAction(){
        $getcapa_url = $_POST['getcapa_url'];
        return $this->render("MBWMSBundle:WMS:preview.html.twig",
            array( "getcapa_url"=>$getcapa_url)
        );
    }
    
    /**
     * shows preview of WMS
    */
    public function addAction(){

        $wms = new WMS();
        $wms->setTitle("NEW WMS");

        $em = $this->get("doctrine.orm.entity_manager");
        $em->persist($wms);
        $em->flush();
        
        $uri = $this->generateUrl("wms_details",array("wmsId"=>$wms->getId()));
        return  new RedirectResponse($uri);
    }

}
