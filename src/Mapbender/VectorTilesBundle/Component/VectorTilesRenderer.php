<?php

namespace Mapbender\VectorTilesBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\Application\ApplicationResolver;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\Resolution;
use Mapbender\PrintBundle\Component\LayerRenderer;
use Mapbender\VectorTilesBundle\Entity\VectorTileInstance;
use Mapbender\VectorTilesBundle\Entity\VectorTileSource;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class VectorTilesRenderer extends LayerRenderer
{
    public function __construct(
        protected string $projectDir,
        protected ApplicationResolver $applicationResolver,
        protected EntityManagerInterface $entityManager,
        protected LoggerInterface $logger,
    )
    {
    }

    private ?string $nodeRoot = null;

    public function addLayer(ExportCanvas $canvas, array $layerDef, Box $extent, array $jobData): void
    {
        /** @var Application $application */
        $application = $jobData['application'];
        $instanceId = $layerDef['sourceId'];
        /** @var ?VectorTileInstance $instance */
        $instance = null;

        if ($application->getSource() === Application::SOURCE_DB) {
            $instance = $this->entityManager->getRepository(VectorTileInstance::class)->find((int) $instanceId);
        } else {
            foreach($application->getSourceInstances() as $sourceInstance) {
                if ($sourceInstance->getId() === $instanceId) {
                    $instance = $sourceInstance;
                    break;
                }
            }
        }

        $multiplier = $instance?->getPrintScaleCorrection() ?? 1.0;
        $referer = $instance?->getSource()?->getReferer();

        $config = [
            ...$layerDef,
            "width" => $canvas->getWidth(),
            "height" => $canvas->getHeight(),
            "dpi" => $canvas->physicalDpi,
            "scaleCorrection" => $multiplier,
            "referer" => $referer,
        ];

        $process = new Process(
            ['node', 'mapbender/src/Mapbender/VectorTilesBundle/Resources/js/print-vectortile.js'],
            $this->projectDir,
            [
                'NODE_PATH' => $this->getNodeRoot(),
                'MB_VT_PRINT_CONFIG' => json_encode($config),
            ]
        );
        $process->run();

        if ($process->isSuccessful()) {
            $layerImage = imagecreatefromstring(base64_decode(trim($process->getOutput())));
            imagecopyresampled($canvas->resource, $layerImage,
                0, 0, 0, 0,
                $canvas->getWidth(), $canvas->getHeight(),
                imagesx($layerImage), imagesy($layerImage));
            imagedestroy($layerImage);
        } else {
            $this->logger->warning("[VectorTilesRenderer] Error rendering vector tile layer: {$process->getErrorOutput()}");
        }
    }

    protected function getNodeRoot(): string
    {
        if ($this->nodeRoot === null) {
            $process = new Process(['npm',  'root', '-g']);
            $process->run();
            $this->nodeRoot = trim($process->getOutput());
        }
        return $this->nodeRoot;
    }

    public function squashLayerDefinitions(array $layerDef, array $nextLayerDef, Resolution $resolution): array|false
    {
        return false;
    }
}
