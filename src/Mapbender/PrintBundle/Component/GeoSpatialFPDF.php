<?php

namespace Mapbender\PrintBundle\Component;

use InvalidArgumentException;
use LogicException;

class GeoSpatialFPDF extends PDF_Extensions
{

    private const COORDINATE_PRECISION = 10;
    private const NORMALIZED_CORNERS = '0 1 0 0 1 0 1 1';

    /** @var array<int, list<string>> */
    private array $geoViewports = [];

    /** @var array<string, array{name: string, resource: string, visible: bool, objectId?: int}> */
    private array $layers = [];

    private ?string $activeLayer = null;

    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4', ?string $resourceDir = null, ?array $customFonts = null)
    {
        parent::__construct($orientation, $unit, $size, $resourceDir, $customFonts);
        $this->PDFVersion = '1.7';
    }

    public function SetGeoViewport(
        float      $x,
        float      $y,
        float      $width,
        float      $height,
        array      $extent,
        int|string $epsg,
    ): void
    {
        if ($this->page === 0) {
            throw new LogicException('AddPage() must be called first.');
        }

        if ($width <= 0 || $height <= 0) {
            throw new InvalidArgumentException('Viewport dimensions must be positive.');
        }

        if (
            $x < 0
            || $y < 0
            || $x + $width > $this->w
            || $y + $height > $this->h
        ) {
            throw new InvalidArgumentException('Viewport must be inside the current page.');
        }

        if (is_string($epsg) && !str_starts_with($epsg, 'EPSG:')) {
            throw new InvalidArgumentException('EPSG must be a number or a string starting with "EPSG:".');
        }
        if (is_string($epsg)) {
            $epsg = (int)substr($epsg, 5);
        }

        if ($epsg <= 0) {
            throw new InvalidArgumentException('EPSG is required.');
        }

        if (
            isset($this->PageInfo[$this->page]['rotation'])
            && $this->PageInfo[$this->page]['rotation'] !== 0
        ) {
            throw new LogicException('Rotated pages are not supported.');
        }

        // FPDF uses a top-left origin; PDF page dictionaries use a bottom-left origin.
        $left = $x * $this->k;
        $bottom = ($this->h - $y - $height) * $this->k;
        $right = ($x + $width) * $this->k;
        $top = ($this->h - $y) * $this->k;

        //$gpts = [
        //    $maxLatitude, $minLongitude,
        //    $minLatitude, $minLongitude,
        //    $minLatitude, $maxLongitude,
        //    $maxLatitude, $maxLongitude,
        //];

        // whoever invented this order ...
        $gpts = [
            $extent[1]['y'], $extent[1]['x'],
            $extent[0]['y'], $extent[0]['x'],
            $extent[3]['y'], $extent[3]['x'],
            $extent[2]['y'], $extent[2]['x'],
        ];

        $wkt = file_get_contents(sprintf('https://spatialreference.org/ref/epsg/%d/ogcwkt/', $epsg));
        $projected = str_starts_with($wkt, 'PROJCS');

        $gcs = sprintf(
            '<</Type ' . ($projected ? '/PROJCS' : '/GEOGCS') . ' /EPSG %d /WKT (%s)>>',
            $epsg,
            $wkt
        );

        $measure = sprintf(
            '<</Type /Measure /Subtype /GEO'
            . ' /Bounds [%s]'
            . ' /LPTS [%s]'
            . ' /GPTS [%s]'
            . ' /GCS %s>>',
            self::NORMALIZED_CORNERS,
            self::NORMALIZED_CORNERS,
            $this->formatNumbers($gpts),
            $gcs
        );

        $this->geoViewports[$this->page][] = sprintf(
            '<</Type /Viewport /BBox [%s] /Measure %s>>',
            $this->formatNumbers([$left, $bottom, $right, $top]),
            $measure
        );
    }

    protected function _putpage($n)
    {
        $this->_newobj();
        $this->_put('<</Type /Page');
        $this->_put('/Parent 1 0 R');

        if (isset($this->PageInfo[$n]['size'])) {
            $this->_put(sprintf(
                '/MediaBox [0 0 %.2F %.2F]',
                $this->PageInfo[$n]['size'][0],
                $this->PageInfo[$n]['size'][1]
            ));
        }

        if (isset($this->PageInfo[$n]['rotation'])) {
            $this->_put('/Rotate ' . $this->PageInfo[$n]['rotation']);
        }

        $this->_put('/Resources 2 0 R');

        if (!empty($this->PageLinks[$n])) {
            $annotations = '/Annots [';

            foreach ($this->PageLinks[$n] as $link) {
                $annotations .= $link[5] . ' 0 R ';
            }

            $this->_put($annotations . ']');
        }

        if ($this->WithAlpha) {
            $this->_put('/Group <</Type /Group /S /Transparency /CS /DeviceRGB>>');
        }

        if (isset($this->geoViewports[$n])) {
            $this->_put('/VP [' . implode(' ', $this->geoViewports[$n]) . ']');
        }

        $this->_put('/Contents ' . ($this->n + 1) . ' 0 R>>');
        $this->_put('endobj');

        if (!empty($this->AliasNbPages)) {
            $this->pages[$n] = str_replace(
                $this->AliasNbPages,
                (string)$this->page,
                $this->pages[$n]
            );
        }

        $this->_putstreamobject($this->pages[$n]);
        $this->_putlinks($n);
    }

    /** @param list<float> $numbers */
    private function formatNumbers(array $numbers): string
    {
        return implode(' ', array_map(
            static function (float $number): string {
                if (!is_finite($number)) {
                    throw new InvalidArgumentException('Coordinates must be finite.');
                }

                $formatted = sprintf(
                    '%.' . self::COORDINATE_PRECISION . 'F',
                    $number
                );

                return rtrim(rtrim($formatted, '0'), '.');
            },
            $numbers
        ));
    }

    public function AddLayer(string $id, string $name, bool $visible = true): void
    {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $id)) {
            throw new InvalidArgumentException(
                'Layer ID must begin with a letter and contain only letters, numbers, _ or -.'
            );
        }

        if ($name === '') {
            throw new InvalidArgumentException('Layer name cannot be empty.');
        }

        if (isset($this->layers[$id])) {
            throw new InvalidArgumentException("Layer '{$id}' already exists.");
        }

        $this->layers[$id] = [
            'name' => $name,
            'resource' => 'OC' . (count($this->layers) + 1),
            'visible' => $visible,
        ];
    }


    public function BeginLayer(string $id): void
    {
        if ($this->page === 0) {
            throw new LogicException('AddPage() must be called first.');
        }

        if (!isset($this->layers[$id])) {
            throw new InvalidArgumentException("Layer '{$id}' is not registered.");
        }

        if ($this->activeLayer !== null) {
            throw new LogicException(
                "Layer '{$this->activeLayer}' must be ended first."
            );
        }

        $this->activeLayer = $id;
        $resource = $this->layers[$id]['resource'];

        $this->_out("/OC /{$resource} BDC");
    }

    public function EndLayer(): void
    {
        if ($this->activeLayer === null) {
            throw new LogicException('No layer is currently active.');
        }

        $this->_out('EMC');
        $this->activeLayer = null;
    }

    protected function _putresources()
    {
        $this->_putfonts();
        $this->_putimages();

        foreach ($this->layers as $id => $layer) {
            $this->_newobj();
            $this->layers[$id]['objectId'] = $this->n;

            $this->_put('<<');
            $this->_put('/Type /OCG');
            $this->_put('/Name ' . $this->_textstring($layer['name']));
            $this->_put('>>');
            $this->_put('endobj');
        }

        $this->_newobj(2);
        $this->_put('<<');
        $this->_putresourcedict();
        $this->_put('>>');
        $this->_put('endobj');
    }

    protected function _putresourcedict()
    {
        parent::_putresourcedict();

        if ($this->layers === []) {
            return;
        }

        $this->_put('/Properties <<');

        foreach ($this->layers as $layer) {
            $this->_put(sprintf(
                '/%s %d 0 R',
                $layer['resource'],
                $layer['objectId']
            ));
        }

        $this->_put('>>');
    }

    protected function _putcatalog()
    {
        parent::_putcatalog();

        if ($this->layers === []) {
            return;
        }

        $allLayers = [];
        $hiddenLayers = [];

        foreach ($this->layers as $layer) {
            $reference = $layer['objectId'] . ' 0 R';
            $allLayers[] = $reference;

            if (!$layer['visible']) {
                $hiddenLayers[] = $reference;
            }
        }

        $references = implode(' ', $allLayers);
        $hiddenReferences = implode(' ', $hiddenLayers);

        $this->_put(
            '/OCProperties <<'
            . " /OCGs [{$references}]"
            . ' /D <<'
            . ' /BaseState /ON'
            . " /Order [{$references}]"
            . " /OFF [{$hiddenReferences}]"
            . ' >>'
            . ' >>'
        );
    }

    protected function _enddoc()
    {
        if ($this->activeLayer !== null) {
            throw new LogicException(
                "Layer '{$this->activeLayer}' was not ended."
            );
        }

        parent::_enddoc();
    }
}
