<?php

namespace Mapbender\DigitizerBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
class DigitizerToolbar extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
//        return "mb.???.???.class.title";
        return "Digitizer Toolbar";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
//        return "mb.???.???.class.description";
        return "Digitizer Toolbar";
    }

    /**
     * @inheritdoc
     */
    static public function getTags()
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbDigitizerToolbar';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array('js' => array( 'mapbender.element.digitizerToolbar.js',
                                    '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                                    '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js'),
                    'css' => array('sass/element/digitizertoolbar.scss'),
                    'trans' => array('MapbenderDigitizerBundle:Element:digitizertoolbar.json.twig')
                );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "target" => null
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
        return 'Mapbender\DigitizerBundle\Element\Type\DigitizerToolbarAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderDigitizerBundle:ElementAdmin:digitizertoolbaradmin.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderDigitizerBundle:Element:digitizertoolbar.html.twig', array(
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'configuration' => $this->getConfiguration()
        ));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        
    }

}
