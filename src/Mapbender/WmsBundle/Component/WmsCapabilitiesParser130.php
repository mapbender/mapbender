<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\Keyword;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Component\RequestInformation;

/**
 * Class that Parses WMS 1.3.0 GetCapabilies Document 
 * @package Mapbender
 * @author Paul Schmidt
 */
class WmsCapabilitiesParser130
        extends WmsCapabilitiesParser
{

    /**
     * Creates an instance
     * @param \DOMDocument $doc
     */
    public function __construct(\DOMDocument $doc)
    {
        parent::__construct($doc);

        foreach($this->xpath->query('namespace::*', $this->doc->documentElement) as $node)
        {
            $nsPrefix = $node->prefix;
            $nsUri = $node->nodeValue;
            if($nsPrefix == "" && $nsUri == "http://www.opengis.net/wms")
            {
                $nsPrefix = "wms";
            }
            $this->xpath->registerNamespace($nsPrefix, $nsUri);
        }
    }

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
        $this->parseService($wms, $this->getValue("./wms:Service", $root));
        $capabilities = $this->xpath->query("./wms:Capability/*", $root);
        foreach($capabilities as $capabilityEl)
        {
            if($capabilityEl->localName === "Request")
            {
                $this->parseCapabilityRequest($wms, $capabilityEl);
            } else if($capabilityEl->localName === "Exception")
            {
                $this->parseCapabilityException($wms, $capabilityEl);
            } else if($capabilityEl->localName === "Layer")
            {
                $rootlayer = new WmsLayerSource();
                $wms->addLayer($rootlayer);
                $layer = $this->parseLayer($wms, $rootlayer, $capabilityEl);
            }
            /* parse wms:_ExtendedOperation  */ else if($capabilityEl->localName === "UserDefinedSymbolization")
            {
                $this->parseUserDefinedSymbolization($wms, $capabilityEl);
            }
            /* @TODO add other wms:_ExtendedOperation ?? */
        }
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
        $wms->setName($this->getValue("./wms:Name/text()", $contextElm));
        $wms->setTitle($this->getValue("./wms:Title/text()", $contextElm));
        $wms->setDescription($this->getValue("./wms:Abstract/text()", $contextElm));

        $keywordElList = $this->xpath->query("./wms:KeywordList/wms:Keyword", $contextElm);
        foreach($keywordElList as $keywordEl)
        {
            $keyword = new Keyword();
            $keyword->setValue(trim($this->getValue("./text()", $keywordEl)));
            $keyword->setSourceclass($wms->getClassname());
            $keyword->setSourceid($wms);

            // FIXME: breaks sqlite
            //$wms->addKeyword($keyword);
        }

        $wms->setOnlineResource($this->getValue("./wms:OnlineResource/@xlink:href", $contextElm));

        $wms->setFees($this->getValue("./wms:Fees/text()", $contextElm));
        $wms->setAccessConstraints($this->getValue("./wms:AccessConstraints/text()", $contextElm));

        $layerLimit = intval($this->getValue("./wms:LayerLimit/text()", $contextElm));
        if($layerLimit > 0)
        {
            $wms->setLayerLimit(intval($layerLimit));
        }

        $maxWidth = intval($this->getValue("./wms:MaxWidth/text()", $contextElm));
        if($maxWidth > 0)
        {
            $wms->setMaxWidth(intval($maxWidth));
        }
        $maxHeight = intval($this->getValue("./wms:MaxHeight/text()", $contextElm));
        if($maxHeight > 0)
        {
            $wms->setMaxHeight(intval($maxHeight));
        }

        $contact = new Contact();
        $contact->setPerson($this->getValue("./wms:ContactInformation/wms:ContactPersonPrimary/wms:ContactPerson/text()", $contextElm));
        $contact->setOrganization($this->getValue("./wms:ContactInformation/wms:ContactPersonPrimary/wms:ContactOrganization/text()", $contextElm));
        $contact->setPosition($this->getValue("./wms:ContactInformation/wms:ContactPosition/text()", $contextElm));

        $contact->setAddressType($this->getValue("./wms:ContactInformation/wms:ContactAddress/wms:AddressType/text()", $contextElm));
        $contact->setAddress($this->getValue("./wms:ContactInformation/wms:ContactAddress/wms:Address/text()", $contextElm));
        $contact->setAddressCity($this->getValue("./wms:ContactInformation/wms:ContactAddress/wms:City/text()", $contextElm));
        $contact->setAddressStateOrProvince($this->getValue("./wms:ContactInformation/wms:ContactAddress/wms:StateOrProvince/text()", $contextElm));
        $contact->setAddressPostCode($this->getValue("./wms:ContactInformation/wms:ContactAddress/wms:PostCode/text()", $contextElm));
        $contact->setAddressCountry($this->getValue("./wms:ContactInformation/wms:ContactAddress/wms:Country/text()", $contextElm));

        $contact->setVoiceTelephone($this->getValue("./wms:ContactInformation/wms:ContactVoiceTelephone/text()", $contextElm));
        $contact->setFacsimileTelephone($this->getValue("./wms:ContactInformation/wms:ContactFacsimileTelephone/text()", $contextElm));
        $contact->setElectronicMailAddress($this->getValue("./wms:ContactInformation/wms:ContactElectronicMailAddress/text()", $contextElm));

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
        foreach($operations as $operation)
        {
            if($operation->localName === "GetCapabilities")
            {
                $getCapabilities = $this->parseOperationRequestInformation($operation);
                $wms->setGetCapabilities($getCapabilities);
            } else if($operation->localName === "GetMap")
            {
                $getMap = $this->parseOperationRequestInformation($operation);
                $wms->setGetMap($getMap);
            } else if($operation->localName === "GetFeatureInfo")
            {
                $getFeatureInfo = $this->parseOperationRequestInformation($operation);
                $wms->setGetFeatureInfo($getFeatureInfo);
            }
            /* parse wms:_ExtendedOperation */ else if($operation->localName === "GetLegendGraphic")
            {
                $getLegendGraphic = $this->parseOperationRequestInformation($operation);
                $wms->setGetLegendGraphic($getLegendGraphic);
            } else if($operation->localName === "DescribeLayer")
            {
                $describeLayer = $this->parseOperationRequestInformation($operation);
                $wms->setDescribeLayer($describeLayer);
            } else if($operation->localName === "GetStyles")
            {
                $getStyles = $this->parseOperationRequestInformation($operation);
                $wms->setGetStyles($getStyles);
            } else if($operation->localName === "PutStyles")
            {
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
     * Operation Request Information section
     */
    private function parseOperationRequestInformation(\DOMElement $contextElm)
    {
        $requestImformation = new RequestInformation();
        $tempList = $this->xpath->query("./wms:Format", $contextElm);
        if($tempList !== null)
        {
            foreach($tempList as $item)
            {
                $requestImformation->addFormat($this->getValue("./text()", $item));
            }
        }
        $requestImformation->setHttpGet($this->getValue(
                        "./wms:DCPType/wms:HTTP/wms:Get/wms:OnlineResource/@xlink:href", $contextElm));
        $requestImformation->setHttpPost($this->getValue(
                        "./wms:DCPType/wms:HTTP/wms:Post/wms:OnlineResource/@xlink:href", $contextElm));

        return $requestImformation;
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
        $tempList = $this->xpath->query("./wms:Format", $contextElm);
        if($tempList !== null)
        {
            foreach($tempList as $item)
            {
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
        if($contextElm !== null)
        {
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

        $wmslayer->setName($this->getValue("./wms:Name/text()", $contextElm));
        $wmslayer->setTitle($this->getValue("./wms:Title/text()", $contextElm));
        $wmslayer->setAbstract($this->getValue("./wms:Abstract/text()", $contextElm));

        $keywordElList = $this->xpath->query("./wms:KeywordList/wms:Keyword", $contextElm);
        foreach($keywordElList as $keywordEl)
        {
            $keyword = new Keyword();
            $keyword->setValue(trim($this->getValue("./text()", $keywordEl)));
            $keyword->setSourceclass($wmslayer->getClassname());
            $keyword->setSourceid($wmslayer);

            // FIXME: breaks sqlite
            //$wmslayer->addKeyword($keyword);
        }

        $tempList = $this->xpath->query("./wms:CRS", $contextElm);
        if($tempList !== null)
        {
            foreach($tempList as $item)
            {
                $wmslayer->addSrs($this->getValue("./text()", $item));
            }
        }
        $latlonbboxEl = $this->getValue("./wms:EX_GeographicBoundingBox", $contextElm);
        if($latlonbboxEl !== null)
        {
            $latlonBounds = new BoundingBox();
            $latlonBounds->setSrs("EPSG:4326");
            $latlonBounds->setMinx($this->getValue("./wms:westBoundLongitude/text()", $latlonbboxEl));
            $latlonBounds->setMiny($this->getValue("./wms:southBoundLatitude/text()", $latlonbboxEl));
            $latlonBounds->setMaxx($this->getValue("./wms:eastBoundLongitude/text()", $latlonbboxEl));
            $latlonBounds->setMaxy($this->getValue("./wms:northBoundLatitude/text()", $latlonbboxEl));
            $wmslayer->setLatlonBounds($latlonBounds);
        }

        $tempList = $this->xpath->query("./wms:BoundingBox", $contextElm);
        if($tempList !== null)
        {
            foreach($tempList as $item)
            {
                $bbox = new BoundingBox();
                $bbox->setSrs($this->getValue("./@CRS", $item));
                $bbox->setMinx($this->getValue("./@minx", $item));
                $bbox->setMiny($this->getValue("./@miny", $item));
                $bbox->setMaxx($this->getValue("./@maxx", $item));
                $bbox->setMaxy($this->getValue("./@maxy", $item));
                $wmslayer->addBoundingBox($bbox);
            }
        }

        $attributionEl = $this->getValue("./wms:Attribution", $contextElm);
        if($attributionEl !== null)
        {
            $attribution = new Attribution();
            $attribution->setTitle($this->getValue("./wms:Title/text()", $attributionEl));
            $attribution->setOnlineResource($this->getValue("./wms:OnlineResource/text()", $attributionEl));

            $logoUrl = new LegendUrl();
            $logoUrl->setHeight($this->getValue("./wms:LogoURL/@height", $attributionEl));
            $logoUrl->setWidth($this->getValue("./wms:LogoURL/@width", $attributionEl));
            $onlineResource = new OnlineResource();
            $onlineResource->setHref($this->getValue("./wms:LogoURL/wms:OnlineResource/text()", $attributionEl));
            $onlineResource->setFormat($this->getValue("./wms:LogoURL/wms:Format/text()", $attributionEl));
            $logoUrl->setOnlineResource($onlineResource);
            $attribution->setLogoUrl($logoUrl);
            $wmslayer->setAttribution($attribution);
        }

        $authorityList = $this->xpath->query("./wms:AuthorityURL", $contextElm);
        $identifierList = $this->xpath->query("./wms:Identifier", $contextElm);

        if($authorityList !== null)
        {
            foreach($authorityList as $authorityEl)
            {
                $authority = new Authority();
                $authority->setName($this->getValue("./@name", $authorityEl));
                $authority->setUrl($this->getValue("./wms:OnlineResource/text()", $authorityEl));
                $wmslayer->addAuthority($authority);
            }
        }
        if($identifierList !== null)
        {
            foreach($identifierList as $identifierEl)
            {
                $identifier = new Identifier();
                $identifier->setAuthority($this->getValue("./@authority", $identifierEl));
                $identifier->setValue($this->getValue("./text()", $identifierEl));
                $wmslayer->setIdentifier($identifier);
            }
        }

        $metadataUrlList = $this->xpath->query("./wms:MetadataURL", $contextElm);
        if($metadataUrlList !== null)
        {
            foreach($metadataUrlList as $metadataUrlEl)
            {
                $metadataUrl = new MetadataUrl();
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat($this->getValue("./wms:Format/text()", $metadataUrlEl));
                $onlineResource->setHref($this->getValue("./wms:OnlineResource/text()", $metadataUrlEl));
                $metadataUrl->setOnlineResource($onlineResource);
                $metadataUrl->setType($this->getValue("./@type", $metadataUrlEl));
                $wmslayer->addMetadataUrl($metadataUrl);
            }
        }

        $dimentionList = $this->xpath->query("./wms:Dimension", $contextElm);
        if($dimentionList !== null)
        {
            foreach($dimentionList as $dimensionEl)
            {
                $dimention = new Dimension();
                $dimention->setName($this->getValue("./@name", $dimensionEl)); //($this->getValue("./@CRS", $item));
                $dimention->setUnits($this->getValue("./@units", $dimensionEl));
                $dimention->setUnitSymbol($this->getValue("./@unitSymbol", $dimensionEl));
                $dimention->setDefault($this->getValue("./@default", $dimensionEl));
                $dimention->setMultipleValues($this->getValue("./@multipleValues", $dimensionEl) !== null ? (bool) $this->getValue("./@name", $dimensionEl) : null);
                $dimention->setNearestValue($this->getValue("./@nearestValue", $dimensionEl) !== null ? (bool) $this->getValue("./@name", $dimensionEl) : null);
                $dimention->setCurrent($this->getValue("./@current", $dimensionEl) !== null ? (bool) $this->getValue("./@name", $dimensionEl) : null);
                $dimention->setExtentValue($this->getValue("./text()", $dimensionEl));
                $wmslayer->addDimensionl($dimension);
            }
        }

        $dataUrlList = $this->xpath->query("./wms:DataURL", $contextElm);
        if($dataUrlList !== null)
        {
            foreach($dataUrlList as $dataUrlEl)
            {
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat($this->getValue("./wms:Format/text()", $dataUrlEl));
                $onlineResource->setHref($this->getValue("./wms:OnlineResource/text()", $dataUrlEl));

                $wmslayer->addDataUrl($onlineResource);
            }
        }

        $featureListUrlList = $this->xpath->query("./wms:FeatureListURL", $contextElm);
        if($featureListUrlList !== null)
        {
            foreach($featureListUrlList as $featureListUrlEl)
            {
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat($this->getValue("./wms:Format/text()", $featureListUrlEl));
                $onlineResource->setHref($this->getValue("./wms:OnlineResource/text()", $featureListUrlEl));

                $wmslayer->addFeatureListUrl($onlineResource);
            }
        }

        $tempList = $this->xpath->query("./wms:Style", $contextElm);
        if($tempList !== null)
        {
            foreach($tempList as $item)
            {
                $style = new Style();
                $style->setName($this->getValue("./wms:Name/text()", $item));
                $style->setTitle($this->getValue("./wms:Title/text()", $item));
                $style->setAbstract($this->getValue("./wms:Abstract/text()", $item));

                $legendUrlEl = $this->getValue("./wms:LegendURL", $item);
                if($legendUrlEl !== null)
                {
                    $legendUrl = new LegendUrl();
                    $legendUrl->setWidth($this->getValue("./@width", $legendUrlEl));
                    $legendUrl->setHeight($this->getValue("./@height", $legendUrlEl));
                    $onlineResource = new OnlineResource();
                    $onlineResource->setFormat($this->getValue("./wms:Format/text()", $legendUrlEl));
                    $onlineResource->setHref($this->getValue("./wms:OnlineResource/@xlink:href", $legendUrlEl));
                    $legendUrl->setOnlineResource($onlineResource);
                    $style->setLegendUrl($legendUrl);
                }

                $styleUrlEl = $this->getValue("./wms:StyleSheetURL", $item);
                if($styleUrlEl !== null)
                {
                    $onlineResource = new OnlineResource();
                    $onlineResource->setFormat($this->getValue("./wms:Format/text()", $styleUrlEl));
                    $onlineResource->setHref($this->getValue("./wms:OnlineResource/@xlink:href", $styleUrlEl));
                    $style->setStyleUlr($onlineResource);
                }
                $stylesheetUrlEl = $this->getValue("./wms:StyleSheetURL", $item);
                if($stylesheetUrlEl !== null)
                {
                    $onlineResource = new OnlineResource();
                    $onlineResource->setFormat($this->getValue("./wms:Format/text()", $stylesheetUrlEl));
                    $onlineResource->setHref($this->getValue("./wms:OnlineResource/@xlink:href", $stylesheetUrlEl));
                    $style->setStyleSheetUrl($onlineResource);
                }

                $wmslayer->addStyle($style);
            }
        }

        $minScaleEl = $this->getValue("./wms:MinScaleDenominator", $contextElm);
        $maxScaleEl = $this->getValue("./wms:MaxScaleDenominator", $contextElm);
        if($minScaleEl !== null || $maxScaleEl !== null)
        {
            $scale = new MinMax();
            $min = $this->getValue("./text()", $minScaleEl);
            $scale->setMin($min !== null ? floatval($min) : null);
            $max = $this->getValue("./text()", $maxScaleEl);
            $scale->setMax($max !== null ? floatval($max) : null);
            $wmslayer->setScale($scale);
            
            $scaleHint = new MinMax();
            $minScaleHint = sqrt(2.0)*$scale->getMin()/($this->resolution/2.54*100);
            $maxScaleHint = sqrt(2.0)*$scale->getMax()/($this->resolution/2.54*100);

            $scaleHint->setMax($maxScaleHint);
            $scaleHint->setMin($minScaleHint);
            $wmslayer->setScaleHint($scaleHint);
        }

        $tempList = $this->xpath->query("./wms:Layer", $contextElm);
        if($tempList !== null)
        {
            foreach($tempList as $item)
            {
                $subwmslayer = $this->parseLayer($wms, new WmsLayerSource(), $item);
                $subwmslayer->setParent($wmslayer);
                $subwmslayer->setSource($wms);
                $wmslayer->addSublayer($subwmslayer);
                $wms->addLayer($subwmslayer);
            }
        }
        $wmslayer->setSource($wms);
        return $wmslayer;
    }

}

