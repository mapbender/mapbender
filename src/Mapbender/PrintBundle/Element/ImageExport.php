<?php

namespace Mapbender\PrintBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\PrintBundle\Component\ImageExportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 *
 */
class ImageExport extends AbstractElementService implements ElementHttpHandlerInterface
{
    /** @var UrlGeneratorInterface */
    protected $urlGenerator;
    /** @var ImageExportService */
    protected $exportService;
    /** @var UrlProcessor */
    protected $sourceUrlProcessor;

    public function __construct(UrlGeneratorInterface $urlGenerator,
                                ImageExportService $exportService,
                                UrlProcessor $sourceUrlProcessor)
    {
        $this->urlGenerator = $urlGenerator;
        $this->exportService = $exportService;
        $this->sourceUrlProcessor = $sourceUrlProcessor;
    }

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
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbImageExport';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
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
        return '@MapbenderPrint/ElementAdmin/imageexport.html.twig';
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('@MapbenderPrint/Element/imageexport.html.twig');
        $view->attributes['class'] = 'mb-element-imageexport';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables['submitUrl'] = $this->urlGenerator->generate('mapbender_core_application_element', array(
            'slug' => $element->getApplication()->getSlug(),
            'id' => $element->getId(),
            'action' => 'export',
        ));
        return $view;
    }

    public function getHttpHandler(Element $element)
    {
        return $this;
    }

    public function handleRequest(Element $element, Request $request)
    {
        $action = $request->attributes->get('action');
        switch ($action) {
            case 'export':
                $data = $this->prepareJobData($request, $element);
                $format = $request->request->get('imageformat');
                $image = $this->exportService->runJob($data);
                return new Response($this->exportService->dumpImage($image, $format), Response::HTTP_OK, array(
                    'Content-Disposition' => 'attachment; filename=export_' . date('YmdHis') . ".{$format}",
                    'Content-Type' => $this->getMimetype($format),
                ));
            default:
                throw new BadRequestHttpException("No such action");
        }
    }

    protected function prepareJobData(Request $request, Element $element)
    {
        $data = json_decode($request->get('data'), true);
        $data['application'] = $element->getApplication();
        // resolve tunnel requests
        foreach (ArrayUtil::getDefault($data, 'layers', array()) as $ix => $layerData) {
            if (!empty($layerData['url'])) {
                $data['layers'][$ix]['url'] = $this->sourceUrlProcessor->getInternalUrl($element->getApplication(), $layerData['url']);
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
}
