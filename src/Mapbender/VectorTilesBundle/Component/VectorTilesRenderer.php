<?php

namespace Mapbender\VectorTilesBundle\Component;

use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\Resolution;
use Mapbender\PrintBundle\Component\LayerRenderer;
use Symfony\Component\Process\Process;

class VectorTilesRenderer extends LayerRenderer
{
    public function __construct(
        protected string $projectDir,
    )
    {
    }

    private ?string $nodeRoot = null;

    public function addLayer(ExportCanvas $canvas, array $layerDef, Box $extent): void
    {
        $config = [
            ...$layerDef,
            "width" => $canvas->getWidth(),
            "height" => $canvas->getHeight(),
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
