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
    static public function listAssets()
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

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderPrintBundle:Element:imageexport.html.twig', array(
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
