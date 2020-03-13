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
    public function getWidgetName()
    {
        return 'mapbender.mbImageExport';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderPrintBundle/Resources/public/mapbender.element.imageExport.js',
            ),
            'css' => array(
                '@MapbenderPrintBundle/Resources/public/sass/element/imageexport.scss',
            ),
            'trans' => array(
                'mb.print.imageexport.popup.*',
                'mb.print.imageexport.info.*',
            ),
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
                $request = $this->container->get('request_stack')->getCurrentRequest();
                $data = $this->prepareJobData($request, $this->entity->getConfiguration());
                $format = $request->request->get('imageformat');
                $exportservice = $this->getExportService();
                $image = $exportservice->runJob($data);
                return new Response($exportservice->dumpImage($image, $format), 200, array(
                    'Content-Disposition' => 'attachment; filename=export_' . date('YmdHis') . ".{$format}",
                    'Content-Type' => $this->getMimetype($format),
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
     * @param string $format
     * @return string
     */
    public static function getMimetype($format)
    {
        switch ($format) {
            case 'png':
                return 'image/png';
            case 'jpeg':
            case 'jpg':
                return 'image/jpeg';
            default:
                throw new \InvalidArgumentException("Unsupported format $format");
        }
    }

    /**
     * @return ImageExportService
     */
    protected function getExportService()
    {
        /** @var ImageExportService $service */
        $service = $this->container->get('mapbender.imageexport.service');
        return $service;
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
