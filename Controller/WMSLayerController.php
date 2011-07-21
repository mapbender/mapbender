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
class WMSLayerController extends Controller {

    public function getWMS($wmsId){
        $wms = $this->getDoctrine()
            ->getRepository('MBWMSBundle:WMSService')
            ->find($wmsId);
            
        if(!$wms){
            throw new NotFoundHttpException('WMS does not exist');
        }   
        return $wms;

    } 

    /**
     * Shows the details of a WMS
     * @Route("/{wmsId}/layer/{id}", name="mb_wmslayer_details", requirements = { "id" = "\d+","_method" = "GET" })
     * @Template()
    */
    public function detailsAction($wmsId = null, $id){
        $wms = $this->getWMS($wmsId);

        $wmsLayer = $this->getDoctrine()
            ->getRepository('MBWMSBundle:WMSLayer')
            ->find($id);

        if(!$wmsLayer){
            throw new NotFoundHttpException('WMSLayer does not exist');
        }

        return array(
            "wms" => $wms,
            "wmsLayer" => $wmsLayer
        );
    }

}
