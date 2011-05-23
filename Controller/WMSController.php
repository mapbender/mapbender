<?php

namespace MB\WMSBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use MB\WMSBundle\Entity;
use MB\WMSBundle\Components\CapabilitiesParser;
use MB\WMSBundle\Form\WMSType;

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
        $em = $this->get("doctrine.orm.entity_manager");
        $wms = $em->find('MBWMSBundle:WMS',$wmsId);

        return $this->render("MBWMSBundle:WMS:details.html.twig",
            array(
                'wmsTitle'=>$wms->getTitle(),
                'wmsId' => $wms->getId()
        ));
    }
    
    /**
     * shows the dialog that allows adding a WMS
    */
    public function showaddAction(){
        return $this->render("MBWMSBundle:WMS:showadd.html.twig");
    }
    /**
     * shows the dialog for wms Deletion confirmation
    */
    public function showdeleteAction($wmsId){
        return $this->render("MBWMSBundle:WMS:showdelete.html.twig",
            array(
                'wmsTitle'=>"",
                'wmsId' => $wmsId
        ));
    }
    /**
     * deletes a WMS
    */
    public function deleteAction($wmsId){

        $em = $this->get("doctrine.orm.entity_manager");
        $wms = $em->find('MBWMSBundle:WMS',$wmsId);
        $em->remove($wms);
        $em->flush();

        return $this->render("MBWMSBundle:WMS:delete.html.twig",array("wmsId"=>$wmsId));
    }

    /**
     * shows preview of WMS
    */
    public function previewAction(){
        $getcapa_url = $this->get('request')->request->get('getcapa_url');
        $data = file_get_contents($getcapa_url);
        // FIXME wrap that datagetting
        $doc = new \DOMDocument();
        $doc->loadXML($data);
        $capaParser = new CapabilitiesParser($doc);
        $wms = $capaParser->getWMS();

        $form = $this->get('form.factory')->create(new  WMSType(),$wms);
        //$form->bind($wms);

        return $this->render("MBWMSBundle:WMS:preview.html.twig",
            array( 
                "getcapa_url"=>$getcapa_url,
                "wms" => $wms,
                "form" => $form,
                "xml" => $data
            )
        );
    }
    
    /**
     * adds a WMS
    */
    public function addAction(){

        $wms = new Entity\WMS();
        $wms->setTitle("NEW WMS");

        $em = $this->get("doctrine.orm.entity_manager");
        $em->persist($wms);
        $em->flush();
        
        $uri = $this->generateUrl("wms_details",array("wmsId"=>$wms->getId()));
        #$uri = $this->generateUrl("wms_details",array("wmsId"=>4));
        return  new RedirectResponse($uri);
    }


}
