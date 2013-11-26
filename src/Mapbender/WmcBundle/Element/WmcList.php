<?php
namespace Mapbender\WmcBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\WmcBundle\Component\WmcHandler;
use Mapbender\WmcBundle\Entity\Wmc;
use Symfony\Component\HttpFoundation\Response;

class WmcList extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "WmcList";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Generates a selectbox with configurations that can be loaded";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("wmc", "list");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => null,
            "target" => null,
            'label' => false,);
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\WmcBundle\Element\Type\WmcListAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderWmcBundle:ElementAdmin:wmclist.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbWmcList';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        $js = array(
            'mapbender.element.wmclist.js'
        );
        return array(
            'js' => $js,
            'css' => array()
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $config = $this->getConfiguration();
        $html = $this->container->get('templating')
            ->render('MapbenderWmcBundle:Element:wmclist.html.twig',
            array(
            'id' => $this->getId(),
            'configuration' => $config,
            'title' => $this->getTitle()));
        return $html;
    }

    public function httpAction($action)
    {
        switch ($action) {
            case 'list':
                return $this->getWmcList();
                break;
        }
    }

    /**
     * Returns a html encoded list of all wmc documents
     * 
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    protected function getWmcList()
    {

        $wmchandler = new WmcHandler($this, $this->application, $this->container);
        $wmclist = $wmchandler->getWmcList(true);
        $wmces = array();
        foreach ($wmclist as $wmc) {
            $wmces[$wmc->getId()] = $wmc->getState()->getTitle();
        }
        return new Response(json_encode(array("success" => $wmces)), 200,
            array('Content-Type' => 'application/json'));
    }

}