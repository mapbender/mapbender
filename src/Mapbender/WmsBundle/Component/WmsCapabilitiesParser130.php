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
     * Creates an instance
     * @param \DOMDocument $doc
     */
    public function __construct(\DOMDocument $doc)
    {
        parent::__construct($doc);
        foreach ($this->xpath->query('namespace::*', $this->doc->documentElement) as $node) {
            $nsPrefix = $node->prefix;
            $nsUri = $node->nodeValue;
            if ($nsPrefix == "" && $nsUri == "http://www.opengis.net/wms") {
                $nsPrefix = "wms";
            }
            $this->xpath->registerNamespace($nsPrefix, $nsUri);
        }
    }

    /**
     * @param WmsSource $wms
     * @param \DOMElement $cntxt
     */
    protected function parseService(WmsSource $wms, \DOMElement $cntxt)
    {
        parent::parseService($wms, $cntxt);

        $layerLimit = intval($this->getValue("./wms:LayerLimit/text()", $cntxt));
        if ($layerLimit > 0) {
            $wms->setLayerLimit(intval($layerLimit));
        }
        $maxWidth = intval($this->getValue("./wms:MaxWidth/text()", $cntxt));
        if ($maxWidth > 0) {
            $wms->setMaxWidth(intval($maxWidth));
        }
        $maxHeight = intval($this->getValue("./wms:MaxHeight/text()", $cntxt));
        if ($maxHeight > 0) {
            $wms->setMaxHeight(intval($maxHeight));
        }
    }

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

        foreach ($this->getChildNodesByTagName($contextElm, 'CRS') as $crsEl) {
            $wmslayer->addSrs(\trim($crsEl->textContent));
        }

        $minScaleEl = $this->getValue("./wms:MinScaleDenominator", $contextElm);
        $maxScaleEl = $this->getValue("./wms:MaxScaleDenominator", $contextElm);
        if ($minScaleEl !== null || $maxScaleEl !== null) {
            $min = $this->getValue("./text()", $minScaleEl);
            $max = $this->getValue("./text()", $maxScaleEl);
            $min = $min !== null ? floatval($min) : null;
            $max = $max !== null ? floatval($max) : null;
            $wmslayer->setScale(new MinMax($min, $max));
        }
        return $wmslayer;
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
