<?php

namespace MB\WMSBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
class WMSLayerController extends Controller {

    /**
     * Shows the WMSlayer in an Editor
     * @Route("/{wmsId}/layer/{id}")
     * @Method({"GET"})
     * @Template()
    */
    public function editAction($wmsId = null, $id){

        $wmsLayer = $this->getDoctrine()
            ->getRepository('MBWMSBundle:WMSLayer')
            ->find($id);

        if(!$wmsLayer){
            throw new NotFoundHttpException('WMSLayer does not exist');
        }
        
        $wms = $wmsLayer->getWMS(); $this->getWMS($wmsId);
        if($wmsId != $wms->getId()){
            throw new NotFoundHttpException('WMSLayer does not exist');
        } 

        $form = $this->get('form.factory')->create(new WMSLayerType(),$wmsLayer); 
        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * FIXME: can be removed
     * Shows the details of a WMS
     * @Route("/{wmsId}/layer/{id}")
     * @Method({"GET"})
     * @Template()
    */
    public function detailsAction($wmsId = null, $id){

        $wmsLayer = $this->getDoctrine()
            ->getRepository('MBWMSBundle:WMSLayer')
            ->find($id);

        if(!$wmsLayer){
            throw new NotFoundHttpException('WMSLayer does not exist');
        }
        
        $wms = $wmsLayer->getWMS(); $this->getWMS($wmsId);
        if($wmsId != $wms->getId()){
            throw new NotFoundHttpException('WMSLayer does not exist');
        } 

        return array(
            "wms" => $wms,
            "wmsLayer" => $wmsLayer
        );
    }

}
