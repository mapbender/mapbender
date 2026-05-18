<?php

namespace Mapbender\PrintBundle\Element;

use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\PrintBundle\Component\Plugin\PrintQueuePlugin;
use Mapbender\PrintBundle\Element\Type\BatchPrintClientAdminType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batch Print Client Element
 * 
 * Extends PrintClient with multiframe/serial printing support.
 * Handles batch print requests by preparing job data for multiple frames
 * and submitting them either to the print queue or for direct processing.
 */
class BatchPrintClient extends PrintClient
{
    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.batchprintclient.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.batchprintclient.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return array_merge(parent::getDefaultConfiguration(), [
            'enableGeofileUpload' => true,
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return BatchPrintClientAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderPrint/ElementAdmin/batchprintclient.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbBatchPrintClient';
    }

    public function getRequiredAssets(Element $element): array
    {
        $upstream = parent::getRequiredAssets($element);
        return [
            'js' => array_merge($upstream['js'], [
                '@MapbenderCoreBundle/Resources/public/mapbender-model/GeometryUtil.js',
                '@MapbenderPrintBundle/Resources/public/element/batchprintclient/styleconfig.batchprintclient.js',
                '@MapbenderPrintBundle/Resources/public/element/batchprintclient/framemanager.batchprintclient.js',
                '@MapbenderPrintBundle/Resources/public/element/batchprintclient/rotationcontroller.batchprintclient.js',
                '@MapbenderPrintBundle/Resources/public/element/batchprintclient/tablecontroller.batchprintclient.js',
                '@MapbenderPrintBundle/Resources/public/element/batchprintclient/geofilehandler.batchprintclient.js',
                '@MapbenderPrintBundle/Resources/public/element/MbBatchPrintClient.js',
            ]),
            'css' => array_merge($upstream['css'], [
                '@MapbenderPrintBundle/Resources/public/sass/element/batchprintclient.scss',
            ]),
            'trans' => array_merge($upstream['trans'] ?? [], [
                'mb.print.printclient.batchprint.*',
            ]),
        ];
    }

    /**
     * Use custom template for batch print settings
     */
    protected function getSettingsTemplate(): string
    {
        return '@MapbenderPrint/Element/batchprintclient-settings.html.twig';
    }

    /**
     * Override to pass additional configuration to the view
     */
    public function getView(Element $element): TemplateView
    {
        $view = parent::getView($element);
        $config = $element->getConfiguration();
        // TemplateView has a public $variables array property
        /** @var TemplateView $view */
        $view->variables['enableGeofileUpload'] = !empty($config['enableGeofileUpload']);
        return $view;
    }

    /**
     * Handle HTTP requests for print actions.
     * Always handles multiframe printing (both queued and direct).
     *
     * @param Element $element
     * @param Request $request
     * @return Response
     */
    public function handleRequest(Element $element, Request $request): Response
    {
        $action = $request->attributes->get('action');

        switch ($action) {
            case PrintQueuePlugin::ELEMENT_ACTION_NAME_QUEUE:
                return $this->handleMultiFrameQueue($request, $element);
                
            case 'print':
                return $this->handleMultiFrameDirect($request, $element);
                
            default:
                // For all other actions, use parent implementation
                return parent::handleRequest($element, $request);
        }
    }

    /**
     * Decodes the batch 'data' parameter from the request into an array of frame definitions.
     * Handles both JSON-encoded strings and plain arrays, and guards against null/invalid values.
     *
     * @param Request $request
     * @return array<int, array>
     */
    protected function extractBatchFrames(Request $request): array
    {
        $raw = $request->get('data');
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        } else {
            $decoded = [];
        }
        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    /**
     * Prepare multiframe print data from request
     *
     * @param Request $request
     * @param Element $element
     * @return array Array of prepared job data for each frame
     */
    private function prepareMultiPrintData(Request $request, Element $element): array
    {
        $frames = $this->extractBatchFrames($request);
        $requestWithoutFrames = $request->duplicate(null, array_diff_key($request->request->all(), ['data' => null]));
        $formData = $this->extractRequestData($requestWithoutFrames);

        $multiFrameJobDataArr = [];
        foreach ($frames as $frameData) {
            $multiFrameJobDataArr[] = $this->preparePrintData(array_merge($formData, $frameData), $element);
        }

        return $multiFrameJobDataArr;
    }

    /**
     * Prepare multiframe job data wrapper - shared logic for both queue and direct processing
     *
     * @param Request $request
     * @param Element $element
     * @return array Job data wrapper structure
     */
    private function prepareMultiFrameJobData(Request $request, Element $element): array
    {
        // Prepare multiframe data
        $multiFrameJobDataArr = $this->prepareMultiPrintData($request, $element);
        
        // Create wrapper structure - PrintService will detect multiFrame flag
        $jobDataWrapper = [
            'frames' => $multiFrameJobDataArr,
            'multiFrame' => true,
        ];
        
        // Add application slug if available
        $application = $element->getApplication();
        if ($application) {
            $jobDataWrapper['application'] = $application->getSlug();
        }
        
        return $jobDataWrapper;
    }

    /**
     * Handle multi-frame queue processing
     *
     * @param Request $request
     * @param Element $element
     * @return Response
     */
    private function handleMultiFrameQueue(Request $request, Element $element): Response
    {
        $jobDataWrapper = $this->prepareMultiFrameJobData($request, $element);
        
        /** @var PrintQueuePlugin $queuePlugin */
        $queuePlugin = $this->pluginRegistry->getPlugin('print-queue');
        $queuePlugin->putJob($jobDataWrapper, $this->generateFilename($element));
        
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Handle multi-frame direct print processing (non-queued)
     *
     * @param Request $request
     * @param Element $element
     * @return Response
     */
    private function handleMultiFrameDirect(Request $request, Element $element): Response
    {
        $jobDataWrapper = $this->prepareMultiFrameJobData($request, $element);
        
        // Use print service directly instead of queue
        $pdfContent = $this->printService->dumpPrint($jobDataWrapper);
        
        // Return PDF response directly
        $filename = $this->generateFilename($element);
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }
}
