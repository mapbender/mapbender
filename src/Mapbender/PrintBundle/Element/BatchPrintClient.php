<?php

namespace Mapbender\PrintBundle\Element;

use Mapbender\CoreBundle\Entity\Element;
use Mapbender\PrintBundle\Component\Plugin\PrintQueuePlugin;
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
    public static function getClassTitle()
    {
        return "Batch Print";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "Batch Print - Serial printing with multiple frames";
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbBatchPrintClient';
    }

    public function getRequiredAssets(Element $element)
    {
        $upstream = parent::getRequiredAssets($element);
        return array(
            'js' => array_merge($upstream['js'], array(
                '@MapbenderPrintBundle/Resources/public/element/batchprintclient.js',
            )),
            'css' => array_merge($upstream['css'], array(
                '@MapbenderPrintBundle/Resources/public/sass/element/batchprintclient.scss',
            )),
            'trans' => array_merge($upstream['trans'] ?? array(), array(
                'mb.print.printclient.batchprint.*',
            )),
        );
    }

    /**
     * Use custom template for batch print settings
     */
    protected function getSettingsTemplate()
    {
        return '@MapbenderPrint/Element/batchprintclient-settings.html.twig';
    }

    /**
     * Handle HTTP requests for print actions.
     * Always handles multiframe printing (both queued and direct).
     *
     * @param Element $element
     * @param Request $request
     * @return Response
     */
    public function handleRequest(Element $element, Request $request)
    {
        $action = $request->attributes->get('action');
        $configuration = $element->getConfiguration();
        
        switch ($action) {
            case PrintQueuePlugin::ELEMENT_ACTION_NAME_QUEUE:
                return $this->handleMultiFrameQueue($request, $configuration, $element);
                
            case 'print':
                return $this->handleMultiFrameDirect($request, $configuration, $element);
                
            default:
                // For all other actions, use parent implementation
                return parent::handleRequest($element, $request);
        }
    }

    /**
     * Prepare multiframe print data from request
     * 
     * @param Request $request
     * @param array $configuration
     * @return array Array of prepared job data for each frame
     */
    private function prepareMultiPrintData($request, $configuration)
    {
        $multiFrameJobDataArr = [];
        $formData = $this->extractRequestData($request);
        $jobData = $request->get("data");
        $jobData = json_decode($jobData, true);

        foreach ($jobData as $frameData) {
            $data = array_merge($formData, $frameData);
            $frameResult = $this->preparePrintData($data, $configuration);
            $multiFrameJobDataArr[] = $frameResult;
        }

        return $multiFrameJobDataArr;
    }

    /**
     * Prepare multiframe job data wrapper - shared logic for both queue and direct processing
     * 
     * @param Request $request
     * @param array $configuration
     * @param Element $element
     * @return array Job data wrapper structure
     */
    private function prepareMultiFrameJobData($request, $configuration, $element)
    {
        // Prepare multiframe data
        $multiFrameJobDataArr = $this->prepareMultiPrintData($request, $configuration);
        
        // Create wrapper structure
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
     * @param array $configuration
     * @param Element $element
     * @return Response JSON response with job ID
     */
    private function handleMultiFrameQueue($request, $configuration, $element)
    {
        // Prepare multiframe job data wrapper using shared logic
        $jobDataWrapper = $this->prepareMultiFrameJobData($request, $configuration, $element);
        
        // Use queue plugin to capture the job ID
        /** @var PrintQueuePlugin $queuePlugin */
        $queuePlugin = $this->pluginRegistry->getPlugin('print-queue');
        $jobId = $queuePlugin->putJob($jobDataWrapper, $this->generateFilename($element));
        
        // Return JSON response with job ID
        return new Response(json_encode(['success' => true, 'jobId' => $jobId]), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Handle multi-frame direct print processing (non-queued)
     * 
     * @param Request $request
     * @param array $configuration
     * @param Element $element
     * @return Response PDF response
     */
    private function handleMultiFrameDirect($request, $configuration, $element)
    {
        // Prepare multiframe job data
        $jobDataWrapper = $this->prepareMultiFrameJobData($request, $configuration, $element);
        
        // Use print service directly instead of queue
        $pdfContent = $this->printService->dumpPrint($jobDataWrapper, true); // multiFrame = true
        
        // Return PDF response directly
        $filename = $this->generateFilename($element) . '.pdf';
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }
}
