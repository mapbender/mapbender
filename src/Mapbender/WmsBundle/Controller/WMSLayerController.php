<?php

namespace Mapbender\WmsBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Mapbender\WmsBundle\Entity\WMSService;
use Mapbender\WmsBundle\Entity\WMSLayer;
use Mapbender\WmsBundle\Entity\GroupLayer;
use Mapbender\WmsBundle\Component\CapabilitiesParser;
use Mapbender\WmsBundle\Form\WMSType;

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
