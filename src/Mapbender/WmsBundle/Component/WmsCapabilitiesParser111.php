<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\BoundingBox;
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
     * @param WmsSource $wms
     * @param \DOMElement $contextElm
     * @return WmsLayerSource the created layer
     */
    protected function parseLayer(WmsSource $wms, \DOMElement $contextElm)
    {
        $wmslayer = parent::parseLayer($wms, $contextElm);

        foreach ($this->getChildNodesByTagName($contextElm, 'SRS') as $srsEl) {
            $wmslayer->addSrs(\trim($srsEl->textContent));
        }

        $scaleHintEl = $this->getValue("./ScaleHint", $contextElm);
        if ($scaleHintEl !== null) {
            $minScaleHint = $this->getValue("./@min", $scaleHintEl);
            $maxScaleHint = $this->getValue("./@max", $scaleHintEl);
            $minScaleHint = $minScaleHint !== null ? floatval($minScaleHint) : null;
            $maxScaleHint = $maxScaleHint !== null ? floatval($maxScaleHint) : null;
            $minScale = !$minScaleHint ? null : round(($minScaleHint / sqrt(2.0)) * $this->resolution / 2.54 * 100);
            $maxScale = !$maxScaleHint ? null : round(($maxScaleHint / sqrt(2.0)) * $this->resolution / 2.54 * 100);
            $wmslayer->setScale(new MinMax($minScale, $maxScale));
        }

        return $wmslayer;
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
