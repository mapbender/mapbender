<?php

namespace Mapbender\PrintBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\PrintBundle\Component\ImageExportService;;

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
        return "mb.print.imageexport.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.print.imageexport.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getTags()
    {
        return array(
            "mb.print.imageexport.tag.image",
            "mb.print.imageexport.tag.export",
            "mb.print.imageexport.tag.jpeg",
            "mb.print.imageexport.tag.png");
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
            'css' => array(
                'sass/element/imageexport.scss'),
            'trans' => array('MapbenderPrintBundle:Element:imageexport.json.twig'));
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
    public static function getType()
    {
        return 'Mapbender\PrintBundle\Element\Type\ImageExportAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderPrintBundle:ElementAdmin:imageexport.html.twig';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderPrintBundle:Element:imageexport.html.twig';
    }

    public function getFrontendTemplateVars()
    {
        $router = $this->container->get('router');
        $submitUrl = $router->generate('mapbender_core_application_element', array(
            'slug' => $this->application->getEntity()->getSlug(),
            'id' => $this->entity->getId(),
            'action' => 'export',
        ));
        return array(
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'submitUrl' => $submitUrl,
            'formTarget' => '',
        );
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        switch ($action) {

            case 'export':
                $request = $this->container->get('request');
                $data = $request->get('data');
                $exportservice = $this->getExportService();
                $exportservice->export($data);
        }
    }

    /**
     * @return ImageExportService
     */
    protected function getExportService()
    {
        $exportServiceClassName = $this->container->getParameter('mapbender.imageexport.service.class');
        return new $exportServiceClassName($this->container);
    }
}
