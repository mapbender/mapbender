<?php

namespace Mapbender\VectorTilesBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\Resolution;
use Mapbender\PrintBundle\Component\LayerRenderer;
use Mapbender\VectorTilesBundle\Entity\VectorTileInstance;
use Mapbender\VectorTilesBundle\Entity\VectorTileSource;
use Symfony\Component\Process\Process;

class VectorTilesRenderer extends LayerRenderer
{
    public function __construct(
        protected string $projectDir,
        protected EntityManagerInterface $entityManager,
    )
    {
    }

    private ?string $nodeRoot = null;

    public function addLayer(ExportCanvas $canvas, array $layerDef, Box $extent): void
    {
        /** @var VectorTileInstance $instance */
        $instance = $this->entityManager->getRepository(VectorTileInstance::class)->find($layerDef['sourceId']);
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

        $processIsSuccessful = $process->isSuccessful();
        $processError = $process->getErrorOutput();
        $processOutput = $process->getOutput();

        if ($processIsSuccessful) {
            $layerImage = imagecreatefromstring(base64_decode(trim($processOutput)));
            imagecopyresampled($canvas->resource, $layerImage,
                0, 0, 0, 0,
                $canvas->getWidth(), $canvas->getHeight(),
                imagesx($layerImage), imagesy($layerImage));
            imagedestroy($layerImage);
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
