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
use MB\WMSBundle\Component\CapabilitiesParser;
use MB\WMSBundle\Form\WMSType;

/*
* @package bkg
* @author Karim Malhas <karim@malhas.de>
*/
class WMSLayerController extends Controller {

    /**
     * Shows the WMSlayer in an Editor
     * @Route("/{wmsId}/layer/{wmsLayerId}")
     * @Method({"GET"})
     * @Template()
    */
    public function editAction(WMSService $wms, WMSLayer $wmsLayer){
        $form = $this->get('form.factory')->create(new WMSLayerType(),$wmsLayer); 
        return array(
            'form' => $form->createView(),
        );
    }

}
