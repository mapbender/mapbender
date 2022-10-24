<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace Mapbender\WmsBundle\Component;

use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;


/**
 * @package Mapbender
 * @author Paul Schmidt
 */
class WmsCapabilitiesParser111 extends WmsCapabilitiesParser
{

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

        foreach ($this->getChildNodesByTagName($layerEl, 'SRS') as $srsEl) {
            /**
             * NOTE: this may be an empty tag
             * See WMS 1.1.1, section 7.1.4.5.5 "SRS"
             * "Use a single SRS element with empty content (like so: "<SRS></SRS>") if
             * there is no common SRS."
             * oO
             */
            $srs = \trim($srsEl->textContent);
            if ($srs) {
                $layer->addSrs($srs);
            }
        }

        $scaleHintEl = $this->getFirstChildNode($layerEl, 'ScaleHint');
        if ($scaleHintEl) {
            $minScaleHint = \floatval(\trim($scaleHintEl->getAttribute('min')));
            $maxScaleHint = \floatval(\trim($scaleHintEl->getAttribute('max')));
            $minScale = !$minScaleHint ? null : round(($minScaleHint / sqrt(2.0)) * $this->resolution / 2.54 * 100);
            $maxScale = !$maxScaleHint ? null : round(($maxScaleHint / sqrt(2.0)) * $this->resolution / 2.54 * 100);
            $layer->setScale(new MinMax($minScale, $maxScale));
        }

        return $layer;
    }

    protected function parseLayerBoundingBox(\DOMElement $element = null)
    {
        $bbox = parent::parseLayerBoundingBox($element);
        if ($bbox && $element) {
            $bbox->setSrs($element->getAttribute('SRS'));
        }
        return $bbox;
    }

    protected function getLayerLatLonBounds(\DOMElement $layerEl)
    {
        foreach ($this->getChildNodesByTagName($layerEl, 'LatLonBoundingBox') as $bboxEl) {
            // Same structure as any other BoundingBox element
            $bbox = $this->parseLayerBoundingBox($bboxEl);
            $bbox->setSrs('EPSG:4326');
            return $bbox;
        }
        return null;
    }

    protected function getLayerDimensions(\DOMElement $layerEl)
    {
        $dimensions = parent::getLayerDimensions($layerEl);
        foreach ($this->getChildNodesByTagName($layerEl, 'Extent') as $extentEl) {
            $extentName = $extentEl->getAttribute('name');
            if ($extentName && !empty($dimensions['extentName'])) {
                $dimensions[$extentName]->setDefault($extentEl->getAttribute('default'));
                $dimensions[$extentName]->setMultipleValues(!!$extentEl->getAttribute('multipleValues'));
                $dimensions[$extentName]->setNearestValue(!!$extentEl->getAttribute('nearestValue'));
                $dimensions[$extentName]->setCurrent(!!$extentEl->getAttribute('current'));
                $dimensions[$extentName]->setExtent($extentEl->textContent);
            }
        }
        return $dimensions;
    }
}
