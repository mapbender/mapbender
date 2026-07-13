<?php

namespace Mapbender\PrintBundle\Element;

use Mapbender\PrintBundle\Element\Type\ImageExportAdminType;
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
    public function __construct(protected UrlGeneratorInterface $urlGenerator, protected ImageExportService $exportService, protected UrlProcessor $sourceUrlProcessor)
    {
    }

    /**
     * @inheritdoc
     */
    static public function getClassTitle(): string
    {
        return "mb.print.imageexport.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription(): string
    {
        return "mb.print.imageexport.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbImageExport';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderPrintBundle/Resources/public/MbImageExport.js',
            ],
            'css' => [
                '@MapbenderPrintBundle/Resources/public/sass/element/imageexport.scss',
            ],
            'trans' => [
                'mb.print.imageexport.popup.*',
                'mb.print.imageexport.info.*',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'element_icon' => self::getDefaultIcon(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return ImageExportAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderPrint/ElementAdmin/imageexport.html.twig';
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderPrint/Element/imageexport.html.twig');
        $view->attributes['class'] = 'mb-element-imageexport';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables['submitUrl'] = $this->urlGenerator->generate('mapbender_core_application_element', [
            'slug' => $element->getApplication()->getSlug(),
            'id' => $element->getId(),
            'action' => 'export',
        ]);
        return $view;
    }

    public function getHttpHandler(Element $element): static
    {
        return $this;
    }

    public function handleRequest(Element $element, Request $request): Response
    {
        $action = $request->attributes->get('action');
        switch ($action) {
            case 'export':
                $data = $this->prepareJobData($request, $element);
                $format = $request->request->get('imageformat');
                $image = $this->exportService->runJob($data);
                return new Response($this->exportService->dumpImage($image, $format), Response::HTTP_OK, [
                    'Content-Disposition' => 'attachment; filename=export_' . date('YmdHis') . ".{$format}",
                    'Content-Type' => static::getMimetype($format),
                ]);
            default:
                throw new BadRequestHttpException("No such action");
        }
    }

    protected function prepareJobData(Request $request, Element $element)
    {
        $data = json_decode((string) $request->get('data'), true);
        $data['application'] = $element->getApplication();
        // resolve tunnel requests
        foreach (ArrayUtil::getDefault($data, 'layers', []) as $ix => $layerData) {
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
    public static function getMimetype($format): string
    {
        return match ($format) {
            'png' => 'image/png',
            'jpeg', 'jpg' => 'image/jpeg',
            default => throw new \InvalidArgumentException("Unsupported format $format"),
        };
    }
    public static function getDefaultIcon(): string
    {
        return 'icon-image-export';
    }

}
