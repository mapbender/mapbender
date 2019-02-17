<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Region\FullPage;
use Mapbender\PrintBundle\Component\Transport\ImageTransport;

/**
 * Handles rendering Legends in Print.
 *
 * Because Legends can appear both on the main page and following pages, and can introduce any number of
 * page breaks, this is currently not feasible to implement as a plugin, but warrants its own, special-purpose
 * API.
 *
 * Rewire 'mapbender.print.legend_handler.service' to displace this implementation with your own.
 */
class LegendHandler
{
    /** @var ImageTransport */
    protected $imageTransport;
    /** @var string */
    protected $resourceDir;

    /**
     * @param ImageTransport $imageTransport
     * @param $resourceDir
     */
    public function __construct(ImageTransport $imageTransport, $resourceDir)
    {
        $this->imageTransport = $imageTransport;
        $this->resourceDir = $resourceDir;
    }

    /**
     * Prepares LegendBlock objects for later rendering (potentially across first page + following pages)
     *
     * Override this if you need to generate legends dynamically, or using methods other than a presupplied URL.
     *
     * @param array $printJobData
     * @return LegendBlock[]
     */
    public function collectLegends($printJobData)
    {
        if (empty($printJobData['legends'])) {
            return array();
        }
        $blocks = array();
        foreach ($printJobData['legends'] as $groupIndex => $groupData) {
            foreach ($groupData as $title => $url) {
                $block = $this->prepareUrlBlock($title, $url);
                if ($block) {
                    $blocks[] = $block;
                }
            };
        }
        return $blocks;
    }

    /**
     * Adds a new page to the PDF to render more legends. Also implicitly adds watermarks, if defined in the
     * template and job.
     *
     * @param PrintService $printService
     * @param PDF_Extensions|\FPDF $pdf $pdf
     * @param Template|array $templateData
     * @param array $jobData
     */
    public function addPage($printService, $pdf, $templateData, $jobData)
    {
        // @todo: support something other than hardcoded A4 size in portrait orientation
        $pdf->addPage('P', 'a4');
        $this->addLegendPageImage($printService, $pdf, $templateData, $jobData);
        // @todo: make hard-coded spill page legend title font configurable
        $pdf->SetFont('Arial', 'B', 11);
    }

    /**
     * @param PrintService $printService for addIMageToPdf cooperation
     * @param PDF_Extensions|\FPDF $pdf
     * @param Template|array $templateData
     * @param array $jobData
     */
    protected function addLegendPageImage($printService, $pdf, $templateData, $jobData)
    {
        if (empty($templateData['legendpage_image']) || empty($jobData['legendpage_image'])) {
            return;
        }
        $sourcePath = $this->resourceDir . '/' . $jobData['legendpage_image']['path'];
        $region = $templateData['legendpage_image'];
        if (file_exists($sourcePath)) {
            $printService->addImageToPdf($pdf, $sourcePath, $region['x'], $region['y'], 0, $region['height']);
        } else {
            $defaultPath = $this->resourceDir . '/images/legendpage_image.png';
            if ($defaultPath !== $sourcePath && file_exists($defaultPath)) {
                $printService->addImageToPdf($pdf, $defaultPath, $region['x'], $region['y'], 0, $region['height']);
            }
        }
    }

    /**
     * @param string $title
     * @param string $url
     * @return LegendBlock|null
     */
    public function prepareUrlBlock($title, $url)
    {
        $image = $this->imageTransport->downloadImage($url);
        if ($image) {
            return new LegendBlock($image, $title);
        } else {
            return null;
        }
    }

    /**
     * Returns the desired outer margin around the rendered legends
     *
     * @param TemplateRegion $region
     * @return int[] with keys 'x' and 'y', values in mm
     */
    public function getMargins($region)
    {
        // @todo: config values please
        if ($region instanceof FullPage) {
            return array(
                'x' => 5,
                'y' => 10,
            );
        } else {
            return array(
                'x' => 5,
                'y' => 5,
            );
        }
    }
}
