<?php

namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Legend\LegendBlockGroup;
use Mapbender\Utils\MemoryUtil;

/**
 * Batch Print Service
 * 
 * Extends PrintService with multiframe/serial printing support.
 * Handles the generation of batch PDFs with multiple map frames.
 */
class BatchPrintService extends PrintService
{
    /**
     * Collected legends from all frames for merging
     * @var array
     */
    protected array $collectedLegends = [];

    /**
     * Executes the job (plain array), returns a binary string representation of the resulting PDF.
     * Overrides parent to add multiframe support.
     *
     * @param mixed[] $jobData
     * @param bool $multiFrame
     * @return string
     * @throws \Exception on invalid template
     */
    public function dumpPrint(array $jobData, $multiFrame = false)
    {
        // Handle multiframe structure: {frames: [...], multiFrame: true, ...}
        if (isset($jobData['multiFrame']) && $jobData['multiFrame'] === true) {
            if (!isset($jobData['frames']) || !is_array($jobData['frames'])) {
                throw new \RuntimeException("Invalid multiframe structure: missing 'frames' array");
            }
            return $this->doMultiFramePrint($jobData['frames']);
        } else {
            return parent::doPrint($jobData);
        }
    }

    /**
     * Process multiframe print job
     * 
     * @param array $jobData Array of frame data
     * @return string PDF binary content
     * @throws \Exception
     */
    public function doMultiFramePrint(array $jobData)
    {
        $mapImageNames = [];

        // Create all map images and store filesystem paths
        foreach ($jobData as $index => $data) {
            $templateData = $this->getTemplateData($data);
            
            // CRITICAL: Call setup() to initialize template dimensions before creating map image
            // Each template (A4 quer, A4 hoch, etc.) has different dimensions that must be set
            $this->setup($templateData, $data);
            
            $mapImageNames[] = $this->createMapImage($templateData, $data);
        }

        $pdf = null;
        // Build PDFs for each item
        foreach ($jobData as $index => $data) {
            $templateData = $this->getTemplateData($data);
            $this->setup($templateData, $data);
            
            $pdf = $this->buildMultiFramePdf($mapImageNames[$index], $templateData, $data, $pdf);
        }

        $this->afterMainMapMulti($pdf, $this->getTemplateData($jobData[0]), $jobData[0]);

        return $this->dumpPdf($pdf);
    }

    /**
     * Build PDF for a single frame in multiframe print
     * 
     * @param string $mapImageName Path to map image file
     * @param Template|array $template Template data
     * @param array $jobData Job data for this frame
     * @param PDF_Extensions|\FPDF|null $pdf Existing PDF object or null for first frame
     * @return PDF_Extensions|\FPDF PDF object with added page
     * @throws \Exception
     */
    protected function buildMultiFramePdf($mapImageName, $template, $jobData, $pdf = null)
    {
        if (!$pdf) {
            $pdf = $this->makeBlankPdf($template, $jobData['template']);
        } else {
            if ($template['orientation'] == 'portrait') {
                $format = array($template['pageSize']['width'], $template['pageSize']['height']);
                $orientation = 'P';
            } else {
                $format = array($template['pageSize']['height'], $template['pageSize']['width']);
                $orientation = 'L';
            }
            $pdf->addPage($orientation, $format);
            
            // CRITICAL: Set the source file for this template before importing
            // Each template (A4 quer, A4 hoch, etc.) has its own PDF file
            $pdfPath = $this->templateParser->getTemplateFilePath($jobData['template'], 'pdf');
            $pdf->setSourceFile($pdfPath);
        }
        // PDF_Extensions extends Fpdi, which provides importPage() and useTemplate()
        /** @var \setasign\Fpdi\Fpdi $pdf */
        $tplidx = $pdf->importPage(1);
        $pdf->useTemplate($tplidx);

        $this->addMapImage($pdf, $mapImageName, $template);
        unlink($mapImageName);
        $regionBlacklist = $this->getFirstPageSpecialRegionNames($jobData);
        foreach ($template->getRegions() as $region) {
            if (!in_array($region->getName(), $regionBlacklist)) {
                if (!$this->handleRegion($pdf, $region, $jobData)) {
                    $this->logger->warning("Unhandled print template region " . print_r($region->getName(), true));
                }
            }
        }

        if (!empty($template['fields'])) {
            $this->addTextFields($pdf, $template, $jobData);
        }

        $this->addCoordinates($pdf, $template, $jobData);

        $this->collectedLegends[] = $this->legendHandler->collectLegends($jobData);

        return $pdf;
    }

    /**
     * Process after main map for multiframe print
     * Handles legends and finalization without duplicating textfields/regions
     * 
     * @param PDF_Extensions|\FPDF $pdf PDF object
     * @param Template|array $template Template data
     * @param array $jobData Job data
     */
    protected function afterMainMapMulti($pdf, $template, $jobData)
    {
        $legends = $this->mergeCollectedLegends();

        $this->handleMainPageLegends($pdf, $template, $jobData, $legends);
        $this->finishMainPage($pdf, $template, $jobData);
        $this->handleRemainingLegends($pdf, $template, $jobData, $legends);
    }

    /**
     * Merge collected legends from all frames
     * Deduplicates legend blocks by title across all frames
     * 
     * @return array Merged legend block groups
     */
    protected function mergeCollectedLegends()
    {
        // Collect all unique legend blocks by title
        $uniqueBlocks = [];
        
        foreach ($this->collectedLegends as $collectedLegend) {
            foreach ($collectedLegend as $legendBlockGroup) {
                foreach ($legendBlockGroup->iterateBlocks() as $block) {
                    $uniqueBlocks[$block->getTitle()] = $block;
                }
            }
        }

        // Return as a single merged legend group
        return [new LegendBlockGroup($uniqueBlocks)];
    }
}
