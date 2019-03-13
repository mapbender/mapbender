<?php

namespace Mapbender\WmcBundle\Element;

use Symfony\Component\HttpFoundation\JsonResponse;

class WmcList extends WmcBase
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "mb.wmc.wmclist.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.wmc.wmclist.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("mb.wmc.suggestmap.wmc", "mb.wmc.suggestmap.list");
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
        return array(
            'js' => array(
                '@MapbenderWmcBundle/Resources/public/mapbender.element.wmclist.js',
                '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js'
            ),
            'css' => array(),
            'trans' => array(
                'MapbenderWmcBundle:Element:wmclist.json.twig',
            ),
        );
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderWmcBundle:Element:wmclist.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $config = $this->getConfiguration();
        return $this->container->get('templating')->render($this->getFrontendTemplatePath(), array(
            'id' => $this->getId(),
            'configuration' => $config,
            'title' => $this->getTitle(),
        ));
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
     * @return JsonResponse
     */
    protected function getWmcList()
    {

        $wmchandler = $this->wmcHandlerFactory();
        $wmclist = $wmchandler->getWmcList(true);
        $wmces = array();
        foreach ($wmclist as $wmc) {
            $wmces[$wmc->getId()] = $wmc->getState()->getTitle();
        }
        return new JsonResponse(array(
            "success" => $wmces,
        ));
    }

}
