<?php

namespace Mapbender\PrintBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\PrintBundle\Component\ImageExportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        return array(
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'submitUrl' => $this->getHttpActionUrl('export'),
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
                $data = $this->prepareJobData($request, $this->entity->getConfiguration());
                $format = $data['format'];
                $exportservice = $this->getExportService();
                $image = $exportservice->runJob($data);
                return new Response($exportservice->dumpImage($image, $data['format']), 200, array(
                    'Content-Disposition' => 'attachment; filename=export_' . date('YmdHis') . ".{$format}",
                    'Content-Type' => $exportservice->getMimetype($format),
                ));
            default:
                throw new BadRequestHttpException("No such action");
        }
    }

    protected function prepareJobData(Request $request, $configuration)
    {
        $data = json_decode($request->get('data'), true);
        // resolve tunnel requests
        $processor = $this->getUrlProcessor();
        foreach (ArrayUtil::getDefault($data, 'layers', array()) as $ix => $layerData) {
            if (!empty($layerData['url'])) {
                $data['layers'][$ix]['url'] = $processor->getInternalUrl($layerData['url']);
            }
        }
        return $data;
    }

    /**
     * @return ImageExportService
     */
    protected function getExportService()
    {
        $exportServiceClassName = $this->container->getParameter('mapbender.imageexport.service.class');
        return new $exportServiceClassName($this->container);
    }

    /**
     * @return UrlProcessor
     */
    protected function getUrlProcessor()
    {
        /** @var UrlProcessor $service */
        $service = $this->container->get('mapbender.source.url_processor.service');
        return $service;
    }
}
