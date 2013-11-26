<?php

namespace Mapbender\PrintBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;

/**
 * 
 */
class ImageExport extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "Image Export";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Image Export";
    }

    /**
     * @inheritdoc
     */
    static public function getTags()
    {
        return array('');
    }
    
    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbImageExport';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array('js' => array('mapbender.element.imageExport.js', 
                                   '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                                   '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js'),
                     'css' => array());
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "target" => null,
            "autoOpen" => false
            
        );
    }
    
    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $config = parent::getConfiguration();
        return $config;
    }
    
    /**
     * @inheritdoc
     */
    public static function getType()
    {
        //return 'Mapbender\CoreBundle\Element\Type\PrintClientAdminType';
    }    
    
    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
       // return 'MapbenderCoreBundle:ElementAdmin:printclient.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                        ->render('MapbenderPrintBundle:Element:imageexport.html.twig', 
                        array(
                            'id' => $this->getId(),
                            'title' => $this->getTitle(),
                            'configuration' => $this->getConfiguration()
                        ));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action) {
        switch ($action) {

            case 'export':
                $request = $this->container->get('request');               
                $data = $request->get('data');
                
                // Forward to Printer Service URL using OWSProxy
                $url = $this->container->get('router')->generate('mapbender_print_print_export', array(), true);

                return $this->container->get('http_kernel')->forward(
                                'OwsProxy3CoreBundle:OwsProxy:genericProxy', array(
                                        'url' => $url,
                                        'content' => $data
                                    )
                ); 
        }
    }
}
