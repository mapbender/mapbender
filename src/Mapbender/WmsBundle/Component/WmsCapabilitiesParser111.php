<?php

namespace Mapbender\WmsBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsLayerSourceKeyword;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\WmsBundle\Entity\WmsSourceKeyword;

/**
 * Class that Parses WMS 1.3.0 GetCapabilies Document
 * @package Mapbender
 * @author Paul Schmidt
 */
class WmsCapabilitiesParser111 extends WmsCapabilitiesParser
{

    /**
     * Parses the GetCapabilities document
     *
     * @return \Mapbender\WmsBundle\Entity\WmsSource
     */
    public function parse()
    {
        $wms = new WmsSource();
        $root = $this->doc->documentElement;

        $wms->setVersion($this->getValue("./@version", $root));
        $this->parseService($wms, $this->getValue("./Service", $root));
        $capabilities = $this->xpath->query("./Capability/*", $root);
        foreach ($capabilities as $capabilityEl) {
            if ($capabilityEl->localName === "Request") {
                $this->parseCapabilityRequest($wms, $capabilityEl);
            } elseif ($capabilityEl->localName === "Exception") {
                $this->parseCapabilityException($wms, $capabilityEl);
            } elseif ($capabilityEl->localName === "Layer") {
                $rootlayer = new WmsLayerSource();
                $wms->addLayer($rootlayer);
                $this->parseLayer($wms, $rootlayer, $capabilityEl);
            } elseif ($capabilityEl->localName === "UserDefinedSymbolization") {
                $this->parseUserDefinedSymbolization($wms, $capabilityEl);
            }
        }
        $this->validateDimension($wms->getRootlayer());
        return $wms;
    }

    /**
     * Parses the Service section of the GetCapabilities document
     *
     * @param \Mapbender\WmsBundle\Entity\WmsSource $wms the WmsSource
     * @param \DOMElement $contextElm the element to use as context for
     * the Service section
     */
    private function parseService(WmsSource $wms, \DOMElement $contextElm)
    {
        $wms->setName($this->getValue("./Name/text()", $contextElm));
        $wms->setTitle($this->getValue("./Title/text()", $contextElm));
        $wms->setDescription($this->getValue("./Abstract/text()", $contextElm));

        $keywordElList = $this->xpath->query("./KeywordList/Keyword", $contextElm);
        $keywords = new ArrayCollection();
        foreach ($keywordElList as $keywordEl) {
            $keyword = new WmsSourceKeyword();
            $keyword->setValue(trim($this->getValue("./text()", $keywordEl)));
            $keyword->setReferenceObject($wms);
            $keywords->add($keyword);
        }
        $wms->setKeywords($keywords);

        $wms->setOnlineResource($this->getValue("./OnlineResource/@xlink:href", $contextElm));

        $wms->setFees($this->getValue("./Fees/text()", $contextElm));
        $wms->setAccessConstraints($this->getValue("./AccessConstraints/text()", $contextElm));

        $contact = new Contact();
        $contact->setPerson(
            $this->getValue("./ContactInformation/ContactPersonPrimary/ContactPerson/text()", $contextElm)
        );
        $contact->setOrganization(
            $this->getValue("./ContactInformation/ContactPersonPrimary/ContactOrganization/text()", $contextElm)
        );
        $contact->setPosition($this->getValue("./ContactInformation/ContactPosition/text()", $contextElm));
        $contact->setAddressType(
            $this->getValue("./ContactInformation/ContactAddress/AddressType/text()", $contextElm)
        );
        $contact->setAddress($this->getValue("./ContactInformation/ContactAddress/Address/text()", $contextElm));
        $contact->setAddressCity($this->getValue("./ContactInformation/ContactAddress/City/text()", $contextElm));
        $contact->setAddressStateOrProvince(
            $this->getValue("./ContactInformation/ContactAddress/StateOrProvince/text()", $contextElm)
        );
        $contact->setAddressPostCode(
            $this->getValue("./ContactInformation/ContactAddress/PostCode/text()", $contextElm)
        );
        $contact->setAddressCountry($this->getValue("./ContactInformation/ContactAddress/Country/text()", $contextElm));

        $contact->setVoiceTelephone($this->getValue("./ContactInformation/ContactVoiceTelephone/text()", $contextElm));
        $contact->setFacsimileTelephone(
            $this->getValue("./ContactInformation/ContactFacsimileTelephone/text()", $contextElm)
        );
        $contact->setElectronicMailAddress(
            $this->getValue("./ContactInformation/ContactElectronicMailAddress/text()", $contextElm)
        );

        $wms->setContact($contact);
    }

    /**
     * Parses the Capabilities Request section of the GetCapabilities document
     *
     * @param \Mapbender\WmsBundle\Entity\WmsSource $wms the WmsSource
     * @param \DOMElement $contextElm the element to use as context for the
     * Capabilities Request section
     */
    private function parseCapabilityRequest(WmsSource $wms, \DOMElement $contextElm)
    {
        $operations = $this->xpath->query("./*", $contextElm);
        foreach ($operations as $operation) {
            if ($operation->localName === "GetCapabilities") {
                $getCapabilities = $this->parseOperationRequestInformation($operation);
                $wms->setGetCapabilities($getCapabilities);
            } elseif ($operation->localName === "GetMap") {
                $getMap = $this->parseOperationRequestInformation($operation);
                $wms->setGetMap($getMap);
            } elseif ($operation->localName === "GetFeatureInfo") {
                $getFeatureInfo = $this->parseOperationRequestInformation($operation);
                $wms->setGetFeatureInfo($getFeatureInfo);
            } elseif ($operation->localName === "GetLegendGraphic") {
                $getLegendGraphic = $this->parseOperationRequestInformation($operation);
                $wms->setGetLegendGraphic($getLegendGraphic);
            } elseif ($operation->localName === "DescribeLayer") {
                $describeLayer = $this->parseOperationRequestInformation($operation);
                $wms->setDescribeLayer($describeLayer);
            } elseif ($operation->localName === "GetStyles") {
                $getStyles = $this->parseOperationRequestInformation($operation);
                $wms->setGetStyles($getStyles);
            } elseif ($operation->localName === "PutStyles") {
                $putStyles = $this->parseOperationRequestInformation($operation);
                $wms->setPutStyles($putStyles);
            }
        }
    }

    /**
     * Parses the Operation Request Information section of the GetCapabilities
     * document
     *
     * @param \DOMElement $contextElm the element to use as context for the
     *                                Operation Request Information section
     * @return \Mapbender\WmsBundle\Component\RequestInformation
     */
    private function parseOperationRequestInformation(\DOMElement $contextElm)
    {
        $requestInformation = new RequestInformation();
        $tempList = $this->xpath->query("./Format", $contextElm);
        if ($tempList !== null) {
            foreach ($tempList as $item) {
                $requestInformation->addFormat($this->getValue("./text()", $item));
            }
        }
        $requestInformation->setHttpGet(
            $this->getValue("./DCPType/HTTP/Get/OnlineResource/@xlink:href", $contextElm)
        );
        $requestInformation->setHttpPost(
            $this->getValue("./DCPType/HTTP/Post/OnlineResource/@xlink:href", $contextElm)
        );

        return $requestInformation;
    }

    /**
     * Parses the Capability Exception section of the GetCapabilities
     * document
     *
     * @param \Mapbender\WmsBundle\Entity\WmsSource $wms the WmsSource
     * @param \DOMElement $contextElm the element to use as context for the
     * Capability Exception section
     */
    private function parseCapabilityException(WmsSource $wms, \DOMElement $contextElm)
    {
        $tempList = $this->xpath->query("./Format", $contextElm);
        if ($tempList !== null) {
            foreach ($tempList as $item) {
                $wms->addExceptionFormat($this->getValue("./text()", $item));
            }
        }
    }

    /**
     * Parses the UserDefinedSymbolization section of the GetCapabilities
     * document
     *
     * @param \Mapbender\WmsBundle\Entity\WmsSource $wms the WmsSource
     * @param \DOMElement $contextElm the element to use as context for the
     * UserDefinedSymbolization section
     */
    private function parseUserDefinedSymbolization(WmsSource $wms, \DOMElement $contextElm)
    {
        if ($contextElm !== null) {
            $wms->setSupportSld($this->getValue("./@SupportSLD", $contextElm));
            $wms->setUserLayer($this->getValue("./@UserLayer", $contextElm));
            $wms->setUserStyle($this->getValue("./@UserStyle", $contextElm));
            $wms->setRemoteWfs($this->getValue("./@RemoteWFS", $contextElm));
            $wms->setInlineFeature($this->getValue("./@InlineFeature", $contextElm));
            $wms->setRemoteWcs($this->getValue("./@RemoteWCS", $contextElm));
        }
    }

    /**
     * Parses the Layer section of the GetCapabilities document
     *
     * @param \Mapbender\WmsBundle\Entity\WmsSource $wms the WmsSource
     * @param \Mapbender\WmsBundle\Entity\WmsLayerSource $wmslayer the WmsLayerSource
     * @param \DOMElement $contextElm the element to use as context for the
     * Layer section
     * @return \Mapbender\WmsBundle\Entity\WmsLayerSource the created layer
     */
    private function parseLayer(WmsSource $wms, WmsLayerSource $wmslayer, \DOMElement $contextElm)
    {
        $wmslayer->setQueryable($this->getValue("./@queryable", $contextElm));
        $wmslayer->setCascaded($this->getValue("./@cascaded", $contextElm));
        $wmslayer->setOpaque($this->getValue("./@opaque", $contextElm));
        $wmslayer->setNoSubset($this->getValue("./@noSubsets", $contextElm));
        $wmslayer->setFixedWidth($this->getValue("./@fixedWidth", $contextElm));
        $wmslayer->setFixedHeight($this->getValue("./@fixedHeight", $contextElm));

        $wmslayer->setName($this->getValue("./Name/text()", $contextElm));
        $wmslayer->setTitle($this->getValue("./Title/text()", $contextElm));
        $wmslayer->setAbstract($this->getValue("./Abstract/text()", $contextElm));

        $keywordElList = $this->xpath->query("./KeywordList/Keyword", $contextElm);
        $keywords = new ArrayCollection();
        foreach ($keywordElList as $keywordEl) {
            $keyword = new WmsLayerSourceKeyword();
            $keyword->setValue(trim($this->getValue("./text()", $keywordEl)));
            $keyword->setReferenceObject($wmslayer);
            $keywords->add($keyword);
        }
        $wmslayer->setKeywords($keywords);
        $tempList = $this->xpath->query("./SRS", $contextElm);
        if ($tempList !== null) {
            foreach ($tempList as $item) {
                $wmslayer->addSrs($this->getValue("./text()", $item));
            }
        }
        $latlonbboxEl = $this->getValue("./LatLonBoundingBox", $contextElm);
        if ($latlonbboxEl !== null) {
            $latlonBounds = new BoundingBox();
            $latlonBounds->setSrs("EPSG:4326");
            $latlonBounds->setMinx($this->getValue("./@minx", $latlonbboxEl));
            $latlonBounds->setMiny($this->getValue("./@miny", $latlonbboxEl));
            $latlonBounds->setMaxx($this->getValue("./@maxx", $latlonbboxEl));
            $latlonBounds->setMaxy($this->getValue("./@maxy", $latlonbboxEl));
            $wmslayer->setLatlonBounds($latlonBounds);
        }

        $tempList = $this->xpath->query("./BoundingBox", $contextElm);
        if ($tempList !== null) {
            foreach ($tempList as $item) {
                $bbox = new BoundingBox();
                $bbox->setSrs($this->getValue("./@SRS", $item));
                $bbox->setMinx($this->getValue("./@minx", $item));
                $bbox->setMiny($this->getValue("./@miny", $item));
                $bbox->setMaxx($this->getValue("./@maxx", $item));
                $bbox->setMaxy($this->getValue("./@maxy", $item));
                $wmslayer->addBoundingBox($bbox);
            }
        }
        $attributionEl = $this->getValue("./Attribution", $contextElm);
        if ($attributionEl !== null) {
            $attribution = new Attribution();
            $attribution->setTitle($this->getValue("./Title/text()", $attributionEl));
            $attribution->setOnlineResource($this->getValue("./OnlineResource/@xlink:href", $attributionEl));
            $logoUrl = new LegendUrl();
            $logoUrl->setHeight($this->getValue("./LogoURL/@height", $attributionEl));
            $logoUrl->setWidth($this->getValue("./LogoURL/@width", $attributionEl));
            $onlineResource = new OnlineResource();
            $onlineResource->setHref($this->getValue("./LogoURL/OnlineResource/@xlink:href", $attributionEl));
            $onlineResource->setFormat($this->getValue("./LogoURL/Format/text()", $attributionEl));
            $logoUrl->setOnlineResource($onlineResource);
            $attribution->setLogoUrl($logoUrl);
            $wmslayer->setAttribution($attribution);
        }

        $authorityList = $this->xpath->query("./AuthorityURL", $contextElm);
        $identifierList = $this->xpath->query("./Identifier", $contextElm);
        if ($authorityList !== null) {
            foreach ($authorityList as $authorityEl) {
                $authority = new Authority();
                $authority->setName($this->getValue("./@name", $authorityEl));
                $authority->setUrl($this->getValue("./OnlineResource/@xlink:href", $authorityEl));
                $wmslayer->addAuthority($authority);
            }
        }
        if ($identifierList !== null) {
            foreach ($identifierList as $identifierEl) {
                $identifier = new Identifier();
                $identifier->setAuthority($this->getValue("./@authority", $identifierEl));
                $identifier->setValue($this->getValue("./text()", $identifierEl));
                $wmslayer->setIdentifier($identifier);
            }
        }

        $metadataUrlList = $this->xpath->query("./MetadataURL", $contextElm);
        if ($metadataUrlList !== null) {
            foreach ($metadataUrlList as $metadataUrlEl) {
                $metadataUrl = new MetadataUrl();
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat($this->getValue("./Format/text()", $metadataUrlEl));
                $onlineResource->setHref($this->getValue("./OnlineResource/@xlink:href", $metadataUrlEl));
                $metadataUrl->setOnlineResource($onlineResource);
                $metadataUrl->setType($this->getValue("./@type", $metadataUrlEl));
                $wmslayer->addMetadataUrl($metadataUrl);
            }
        }

        $dimensionList = $this->xpath->query("./Dimension", $contextElm);
        if ($dimensionList !== null) {
            foreach ($dimensionList as $dimensionEl) {
                $dimension = new Dimension();
                $dimension->setName($this->getValue("./@name", $dimensionEl));
                $dimension->setUnits($this->getValue("./@units", $dimensionEl));
                $dimension->setUnitSymbol($this->getValue("./@unitSymbol", $dimensionEl));
                $wmslayer->addDimension($dimension);
            }
        }

        $extentList = $this->xpath->query("./Extent", $contextElm);
        if ($extentList !== null) {
            foreach ($extentList as $extentEl) {
                $extent = array();
                $extent['name'] = $this->getValue("./@name", $extentEl);
                $extent['default'] = $this->getValue("./@default", $extentEl);
                $extent['multiplevalues'] = ($this->getValue("./@multipleValues", $extentEl) !== null ?
                    (bool)$this->getValue("./@name", $extentEl) : null);
                $extent['nearestvalue'] = ($this->getValue("./@nearestValue", $extentEl) !== null ?
                    (bool)$this->getValue("./@name", $extentEl) : null);
                $extent['current'] = ($this->getValue("./@current", $extentEl) !== null ?
                    (bool)$this->getValue("./@name", $extentEl) : null);
                $extent['value'] = $this->getValue("./text()", $extentEl);
                foreach ($wmslayer->getDimension() as $dimension) {
                    if ($dimension->getName() === $extent['name']) {
                        $dimension->setDefault($extent['default']);
                        $dimension->setMultipleValues($extent['multiplevalues']);
                        $dimension->setNearestValue($extent['nearestvalue']);
                        $dimension->setCurrent($extent['current']);
                        $dimension->setExtent($extent['value']);
                    }
                }
            }
        }

        $dataUrlList = $this->xpath->query("./DataURL", $contextElm);
        if ($dataUrlList !== null) {
            foreach ($dataUrlList as $dataUrlEl) {
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat($this->getValue("./Format/text()", $dataUrlEl));
                $onlineResource->setHref($this->getValue("./OnlineResource/@xlink:href", $dataUrlEl));

                $wmslayer->addDataUrl($onlineResource);
            }
        }

        $featureListUrlList = $this->xpath->query("./FeatureListURL", $contextElm);
        if ($featureListUrlList !== null) {
            foreach ($featureListUrlList as $featureListUrlEl) {
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat($this->getValue("./Format/text()", $featureListUrlEl));
                $onlineResource->setHref($this->getValue("./OnlineResource/@xlink:href", $featureListUrlEl));

                $wmslayer->addFeatureListUrl($onlineResource);
            }
        }

        $tempList = $this->xpath->query("./Style", $contextElm);
        if ($tempList !== null) {
            foreach ($tempList as $item) {
                $style = new Style();
                $style->setName($this->getValue("./Name/text()", $item));
                $style->setTitle($this->getValue("./Title/text()", $item));
                $style->setAbstract($this->getValue("./Abstract/text()", $item));

                $legendUrlEl = $this->getValue("./LegendURL", $item);
                if ($legendUrlEl !== null) {
                    $legendUrl = new LegendUrl();
                    $legendUrl->setWidth($this->getValue("./@width", $legendUrlEl));
                    $legendUrl->setHeight($this->getValue("./@height", $legendUrlEl));
                    $onlineResource = new OnlineResource();
                    $onlineResource->setFormat($this->getValue("./Format/text()", $legendUrlEl));
                    $onlineResource->setHref($this->getValue("./OnlineResource/@xlink:href", $legendUrlEl));
                    $legendUrl->setOnlineResource($onlineResource);
                    $style->setLegendUrl($legendUrl);
                }

                $styleUrlEl = $this->getValue("./StyleURL", $item);
                if ($styleUrlEl !== null) {
                    $onlineResource = new OnlineResource();
                    $onlineResource->setFormat($this->getValue("./Format/text()", $styleUrlEl));
                    $onlineResource->setHref($this->getValue("./OnlineResource/@xlink:href", $styleUrlEl));
                    $style->setStyleUlr($onlineResource);
                }

                $stylesheetUrlEl = $this->getValue("./StyleSheetURL", $item);
                if ($stylesheetUrlEl !== null) {
                    $onlineResource = new OnlineResource();
                    $onlineResource->setFormat($this->getValue("./Format/text()", $stylesheetUrlEl));
                    $onlineResource->setHref($this->getValue("./OnlineResource/@xlink:href", $stylesheetUrlEl));
                    $style->setStyleSheetUrl($onlineResource);
                }
                $wmslayer->addStyle($style);
            }
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

        $tempList = $this->xpath->query("./Layer", $contextElm);
        if ($tempList !== null) {
            foreach ($tempList as $item) {
                $subwmslayer = new WmsLayerSource();
                $subwmslayer->setParent($wmslayer);
                $subwmslayer->setSource($wms);
                $wmslayer->addSublayer($subwmslayer);
                $wms->addLayer($subwmslayer);
                $this->parseLayer($wms, $subwmslayer, $item);
            }
        }
        $wmslayer->setSource($wms);
        return $wmslayer;
    }

    private function validateDimension(WmsLayerSource $wmslayer)
    {
        $dimensions = array();
        foreach ($wmslayer->getDimension() as $dimension) {
            if ($dimension->getExtent()) {
                $dimensions[] = $dimension;
            }
        }
        $wmslayer->setDimension($dimensions);
        foreach ($wmslayer->getSublayer() as $sublayer) {
            $this->validateDimension($sublayer);
        }
    }
}
