<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\WmsBundle\Entity\WmsLayerSource;


/**
 * Class that Parses WMS 1.3.0 GetCapabilies Document
 * @package Mapbender
 * @author Paul Schmidt
 */
class WmsCapabilitiesParser130 extends WmsCapabilitiesParser
{
    /**
     * @param WmsSource $source
     * @param \DOMElement $serviceEl
     */
    protected function parseService(WmsSource $source, \DOMElement $serviceEl)
    {
        parent::parseService($source, $serviceEl);
        $layerLimit = \intval(\trim($this->getFirstChildNodeText($serviceEl, 'LayerLimit')));
        if ($layerLimit > 0) {
            $source->setLayerLimit($layerLimit);
        }
        $maxWidth = \intval(\trim($this->getFirstChildNodeText($serviceEl, 'MaxWidth')));
        if ($maxWidth > 0) {
            $source->setMaxWidth($maxWidth);
        }
        $maxHeight = \intval(\trim($this->getFirstChildNodeText($serviceEl, 'MaxHeight')));
        if ($maxHeight > 0) {
            $source->setMaxHeight($maxHeight);
        }
    }

    /**
     * Parses the Layer section of the GetCapabilities document
     *
     * @param WmsSource $source
     * @param \DOMElement $layerEl
     * @return WmsLayerSource
     */
    protected function parseLayer(WmsSource $source, \DOMElement $layerEl)
    {
        $layer = parent::parseLayer($source, $layerEl);

        foreach ($this->getChildNodesByTagName($layerEl, 'CRS') as $crsEl) {
            $layer->addSrs(\trim($crsEl->textContent));
        }
        $minScaleText = \trim($this->getFirstChildNodeText($layerEl, 'MinScaleDenominator'));
        $maxScaleText = \trim($this->getFirstChildNodeText($layerEl, 'MaxScaleDenominator'));

        if (\strlen($minScaleText) || \strlen($maxScaleText)) {
            $min = \strlen($minScaleText) ? $minScaleText : null;
            $max = \strlen($maxScaleText) ? $maxScaleText : null;
            $min = $min !== null ? floatval($min) : null;
            $max = $max !== null ? floatval($max) : null;
            $layer->setScale(new MinMax($min, $max));
        }
        return $layer;
    }

    protected function parseLayerBoundingBox(\DOMElement $element = null)
    {
        $bbox = parent::parseLayerBoundingBox($element);
        if ($bbox && $element) {
            $bbox->setSrs($element->getAttribute('CRS'));
        }
        return $bbox;
    }

    protected function getLayerLatLonBounds(\DOMElement $layerEl)
    {
        foreach ($this->getChildNodesByTagName($layerEl, 'EX_GeographicBoundingBox') as $bboxEl) {
            $bbox = new BoundingBox();
            $bbox->setSrs('EPSG:4326');
            $bbox->setMinx(\trim($this->getFirstChildNodeText($bboxEl, 'westBoundLongitude')));
            $bbox->setMiny(\trim($this->getFirstChildNodeText($bboxEl, 'southBoundLatitude')));
            $bbox->setMaxx(\trim($this->getFirstChildNodeText($bboxEl, 'eastBoundLongitude')));
            $bbox->setMaxy(\trim($this->getFirstChildNodeText($bboxEl, 'northBoundLatitude')));
            return $bbox;
        }
        return null;
    }
}
