<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\Keyword;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Component\RequestInformation;

/**
 * Class that Parses WMS 1.3.0 GetCapabilies Document 
 * @package Mapbender
 * @author Paul Schmidt
 */
class WmsCapabilitiesParser111 extends WmsCapabilitiesParser
{

    /**
     * Creates an instance
     * @param \DOMDocument $doc
     */
    public function __construct(\DOMDocument $doc)
    {
        parent::__construct($doc);
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
        $this->parseService($wms, $this->getValue("./Service", $root));
        $capabilities = $this->xpath->query("./Capability/*", $root);
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
            /* parse _ExtendedOperation  */ else if($capabilityEl->localName === "UserDefinedSymbolization")
            {
                $this->parseUserDefinedSymbolization($wms, $capabilityEl);
            }
            /* @TODO add other _ExtendedOperation ?? */
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
        $wms->setName($this->getValue("./Name/text()", $contextElm));
        $wms->setTitle($this->getValue("./Title/text()", $contextElm));
        $wms->setDescription($this->getValue("./Abstract/text()", $contextElm));

        $keywordElList = $this->xpath->query("./KeywordList/Keyword",
                                             $contextElm);
        foreach($keywordElList as $keywordEl)
        {
            $keyword = new Keyword();
            $keyword->setValue(trim($this->getValue("./text()", $keywordEl)));
            $keyword->setSourceclass($wms->getClassname());
            $keyword->setSourceid($wms);
            //FIXME: breaks sqlite
            //$wms->addKeyword($keyword);
        }

        $wms->setOnlineResource($this->getValue("./OnlineResource/@xlink:href",
                                                $contextElm));

        $wms->setFees($this->getValue("./Fees/text()", $contextElm));
        $wms->setAccessConstraints($this->getValue("./AccessConstraints/text()",
                                                   $contextElm));

        $contact = new Contact();
        $contact->setPerson($this->getValue("./ContactInformation/ContactPersonPrimary/ContactPerson/text()",
                                            $contextElm));
        $contact->setOrganization($this->getValue("./ContactInformation/ContactPersonPrimary/ContactOrganization/text()",
                                                  $contextElm));
        $contact->setPosition($this->getValue("./ContactInformation/ContactPosition/text()",
                                              $contextElm));

        $contact->setAddressType($this->getValue("./ContactInformation/ContactAddress/AddressType/text()",
                                                 $contextElm));
        $contact->setAddress($this->getValue("./ContactInformation/ContactAddress/Address/text()",
                                             $contextElm));
        $contact->setAddressCity($this->getValue("./ContactInformation/ContactAddress/City/text()",
                                                 $contextElm));
        $contact->setAddressStateOrProvince($this->getValue("./ContactInformation/ContactAddress/StateOrProvince/text()",
                                                            $contextElm));
        $contact->setAddressPostCode($this->getValue("./ContactInformation/ContactAddress/PostCode/text()",
                                                     $contextElm));
        $contact->setAddressCountry($this->getValue("./ContactInformation/ContactAddress/Country/text()",
                                                    $contextElm));

        $contact->setVoiceTelephone($this->getValue("./ContactInformation/ContactVoiceTelephone/text()",
                                                    $contextElm));
        $contact->setFacsimileTelephone($this->getValue("./ContactInformation/ContactFacsimileTelephone/text()",
                                                        $contextElm));
        $contact->setElectronicMailAddress($this->getValue("./ContactInformation/ContactElectronicMailAddress/text()",
                                                           $contextElm));

        $wms->setContact($contact);
    }

    /**
     * Parses the Capabilities Request section of the GetCapabilities document
     * 
     * @param \Mapbender\WmsBundle\Entity\WmsSource $wms the WmsSource
     * @param \DOMElement $contextElm the element to use as context for the
     * Capabilities Request section
     */
    private function parseCapabilityRequest(WmsSource $wms,
            \DOMElement $contextElm)
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
            } else if($operation->localName === "GetLegendGraphic")/* parse _ExtendedOperation */
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
        $tempList = $this->xpath->query("./Format", $contextElm);
        if($tempList !== null)
        {
            foreach($tempList as $item)
            {
                $requestImformation->addFormat($this->getValue("./text()", $item));
            }
        }
        $requestImformation->setHttpGet($this->getValue(
                        "./DCPType/HTTP/Get/OnlineResource/@xlink:href",
                        $contextElm));
        $requestImformation->setHttpPost($this->getValue(
                        "./DCPType/HTTP/Post/OnlineResource/@xlink:href",
                        $contextElm));

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
    private function parseCapabilityException(WmsSource $wms,
            \DOMElement $contextElm)
    {
        $tempList = $this->xpath->query("./Format", $contextElm);
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
    private function parseUserDefinedSymbolization(WmsSource $wms,
            \DOMElement $contextElm)
    {
        if($contextElm !== null)
        {
            $wms->setSupportSld($this->getValue("./@SupportSLD", $contextElm));
            $wms->setUserLayer($this->getValue("./@UserLayer", $contextElm));
            $wms->setUserStyle($this->getValue("./@UserStyle", $contextElm));
            $wms->setRemoteWfs($this->getValue("./@RemoteWFS", $contextElm));
            $wms->setInlineFeature($this->getValue("./@InlineFeature",
                                                   $contextElm));
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
    private function parseLayer(WmsSource $wms, WmsLayerSource $wmslayer,
            \DOMElement $contextElm)
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

        $keywordElList = $this->xpath->query("./KeywordList/Keyword",
                                             $contextElm);
        foreach($keywordElList as $keywordEl)
        {
            $keyword = new Keyword();
            $keyword->setValue(trim($this->getValue("./text()", $keywordEl)));
            $keyword->setSourceclass($wmslayer->getClassname());
            $keyword->setSourceid($wmslayer);
            // FIXME: breaks sqlite
            //$wmslayer->addKeyword($keyword);
        }

        $tempList = $this->xpath->query("./SRS", $contextElm);
        if($tempList !== null)
        {
            foreach($tempList as $item)
            {
                $wmslayer->addSrs($this->getValue("./text()", $item));
            }
        }
        $latlonbboxEl = $this->getValue("./LatLonBoundingBox", $contextElm);
        if($latlonbboxEl !== null)
        {
            $latlonBounds = new BoundingBox();
            $latlonBounds->setSrs("EPSG:4326");
            $latlonBounds->setMinx($this->getValue("./@minx", $latlonbboxEl));
            $latlonBounds->setMiny($this->getValue("./@miny", $latlonbboxEl));
            $latlonBounds->setMaxx($this->getValue("./@maxx", $latlonbboxEl));
            $latlonBounds->setMaxy($this->getValue("./@maxy", $latlonbboxEl));
            //@TODO  resx="0.01" resy="0.01" ??
            $wmslayer->setLatlonBounds($latlonBounds);
        }

        $tempList = $this->xpath->query("./BoundingBox", $contextElm);
        if($tempList !== null)
        {
            foreach($tempList as $item)
            {
                $bbox = new BoundingBox();
                $bbox->setSrs($this->getValue("./@SRS", $item));
                $bbox->setMinx($this->getValue("./@minx", $item));
                $bbox->setMiny($this->getValue("./@miny", $item));
                $bbox->setMaxx($this->getValue("./@maxx", $item));
                $bbox->setMaxy($this->getValue("./@maxy", $item));
                //@TODO  resx="0.01" resy="0.01" ??
                $wmslayer->addBoundingBox($bbox);
            }
        }
        /* @TODO Dimension <element ref="Dimension" minOccurs="0" maxOccurs="unbounded"/>
         * <element name="Dimension">
         * <annotation><documentation>The Dimension element declares the existence of a dimension and indicates what values along a dimension are valid.</documentation></annotation>
         * <complexType><simpleContent><extension base="string">
         * <attribute name="name" type="string" use="required"/>
         * <attribute name="units" type="string" use="required"/>
         * <attribute name="unitSymbol" type="string"/>
         * <attribute name="default" type="string"/>
         * <attribute name="multipleValues" type="boolean"/>
         * <attribute name="nearestValue" type="boolean"/>
         * <attribute name="current" type="boolean"/>
         * </extension></simpleContent></complexType>
         * </element>
         */
        $attributionEl = $this->getValue("./Attribution", $contextElm);
        if($attributionEl !== null)
        {
            $attribution = new Attribution();
            $attribution->setTitle($this->getValue("./Title/text()",
                                                   $attributionEl));
            $attribution->setOnlineResource($this->getValue("./OnlineResource/@xlink:href",
                                                            $attributionEl));
            $logoUrl = new LegendUrl();
            $logoUrl->setHeight($this->getValue("./LogoURL/@height",
                                                $attributionEl));
            $logoUrl->setWidth($this->getValue("./LogoURL/@width",
                                               $attributionEl));
            $onlineResource = new OnlineResource();
            $onlineResource->setHref($this->getValue("./LogoURL/OnlineResource/@xlink:href",
                                                     $attributionEl));
            $onlineResource->setFormat($this->getValue("./LogoURL/Format/text()",
                                                       $attributionEl));
            $logoUrl->setOnlineResource($onlineResource);
            $attribution->setLogoUrl($logoUrl);
            $wmslayer->setAttribution($attribution);
        }

        $authorityList = $this->xpath->query("./AuthorityURL", $contextElm);
        $identifierList = $this->xpath->query("./Identifier", $contextElm);
        if($authorityList !== null)
        {
            foreach($authorityList as $authorityEl)
            {
                $authority = new Authority();
                $authority->setName($this->getValue("./@name", $authorityEl));
                $authority->setUrl($this->getValue("./OnlineResource/@xlink:href",
                                                   $authorityEl));
                $wmslayer->addAuthority($authority);
            }
        }
        if($identifierList !== null)
        {
            foreach($identifierList as $identifierEl)
            {
                $identifier = new Identifier();
                $identifier->setAuthority($this->getValue("./@authority",
                                                          $identifierEl));
                $identifier->setValue($this->getValue("./text()", $identifierEl));
                $wmslayer->setIdentifier($identifier);
            }
        }

        $metadataUrlList = $this->xpath->query("./MetadataURL", $contextElm);
        if($metadataUrlList !== null)
        {
            foreach($metadataUrlList as $metadataUrlEl)
            {
                $metadataUrl = new MetadataUrl();
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat($this->getValue("./Format/text()",
                                                           $metadataUrlEl));
                $onlineResource->setHref($this->getValue("./OnlineResource/@xlink:href",
                                                         $metadataUrlEl));
                $metadataUrl->setOnlineResource($onlineResource);
                $metadataUrl->setType($this->getValue("./@type", $metadataUrlEl));
                $wmslayer->addMetadataUrl($metadataUrl);
            }
        }

        $dimensionList = $this->xpath->query("./Dimension", $contextElm);
        if($dimensionList !== null)
        {
            foreach($dimensionList as $dimensionEl)
            {
                $dimension = new Dimension();
                $dimension->setName($this->getValue("./@name", $dimensionEl)); //($this->getValue("./@CRS", $item));
                $dimension->setUnits($this->getValue("./@units", $dimensionEl));
                $dimension->setUnitSymbol($this->getValue("./@unitSymbol",
                                                          $dimensionEl));
                $wmslayer->addDimensionl($dimension);
            }
        }

        $extentList = $this->xpath->query("./Extent", $contextElm);
        if($extentList !== null)
        {
            foreach($extentList as $extentEl)
            {
                $extent = new Extent();
                $extent->setName($this->getValue("./@name", $extentEl));
                $extent->setDefault($this->getValue("./@default", $extentEl));
                $extent->setMultipleValues($this->getValue("./@multipleValues",
                                                           $extentEl) !== null ? (bool) $this->getValue("./@name",
                                                                                                        $extentEl)
                                    : null);
                $extent->setNearestValue($this->getValue("./@nearestValue",
                                                         $extentEl) !== null ? (bool) $this->getValue("./@name",
                                                                                                      $extentEl)
                                    : null);
                $extent->setCurrent($this->getValue("./@current", $extentEl) !== null
                                    ? (bool) $this->getValue("./@name",
                                                             $extentEl) : null);
                $extent->setExtentValue($this->getValue("./text()", $extentEl));
                $wmslayer->addExtent($extent);
            }
        }

        $dataUrlList = $this->xpath->query("./DataURL", $contextElm);
        if($dataUrlList !== null)
        {
            foreach($dataUrlList as $dataUrlEl)
            {
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat($this->getValue("./Format/text()",
                                                           $dataUrlEl));
                $onlineResource->setHref($this->getValue("./OnlineResource/@xlink:href",
                                                         $dataUrlEl));

                $wmslayer->addDataUrl($onlineResource);
            }
        }

        $featureListUrlList = $this->xpath->query("./FeatureListURL",
                                                  $contextElm);
        if($featureListUrlList !== null)
        {
            foreach($featureListUrlList as $featureListUrlEl)
            {
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat($this->getValue("./Format/text()",
                                                           $featureListUrlEl));
                $onlineResource->setHref($this->getValue("./OnlineResource/@xlink:href",
                                                         $featureListUrlEl));

                $wmslayer->addFeatureListUrl($onlineResource);
            }
        }

        $tempList = $this->xpath->query("./Style", $contextElm);
        if($tempList !== null)
        {
            foreach($tempList as $item)
            {
                $style = new Style();
                $style->setName($this->getValue("./Name/text()", $item));
                $style->setTitle($this->getValue("./Title/text()", $item));
                $style->setAbstract($this->getValue("./Abstract/text()", $item));

                $legendUrlEl = $this->getValue("./LegendURL", $item);
                if($legendUrlEl !== null)
                {
                    $legendUrl = new LegendUrl();
                    $legendUrl->setWidth($this->getValue("./@width",
                                                         $legendUrlEl));
                    $legendUrl->setHeight($this->getValue("./@height",
                                                          $legendUrlEl));
                    $onlineResource = new OnlineResource();
                    $onlineResource->setFormat($this->getValue("./Format/text()",
                                                               $legendUrlEl));
                    $onlineResource->setHref($this->getValue("./OnlineResource/@xlink:href",
                                                             $legendUrlEl));
                    $legendUrl->setOnlineResource($onlineResource);
                    $style->setLegendUrl($legendUrl);
                }

                $styleUrlEl = $this->getValue("./StyleURL", $item);
                if($styleUrlEl !== null)
                {
                    $onlineResource = new OnlineResource();
                    $onlineResource->setFormat($this->getValue("./Format/text()",
                                                               $styleUrlEl));
                    $onlineResource->setHref($this->getValue("./OnlineResource/@xlink:href",
                                                             $styleUrlEl));
                    $style->setStyleUlr($onlineResource);
                }

                $stylesheetUrlEl = $this->getValue("./StyleSheetURL", $item);
                if($stylesheetUrlEl !== null)
                {
                    $onlineResource = new OnlineResource();
                    $onlineResource->setFormat($this->getValue("./Format/text()",
                                                               $stylesheetUrlEl));
                    $onlineResource->setHref($this->getValue("./OnlineResource/@xlink:href",
                                                             $stylesheetUrlEl));
                    $style->setStyleSheetUrl($onlineResource);
                }
                $wmslayer->addStyle($style);
            }
        }
        $scaleHintEl = $this->getValue("./ScaleHint", $contextElm);
        if($scaleHintEl !== null)
        {
            $scaleHint = new MinMax();
            $min = $this->getValue("./@min", $scaleHintEl);
            $scaleHint->setMin($min !== null ? floatval($min) : null);
            $max = $this->getValue("./@max", $scaleHintEl);
            $scaleHint->setMax($max !== null ? floatval($max) : null);
            $wmslayer->setScaleHint($scaleHint);
            $min = 0;
            $max = 0;
            if($scaleHint->getMax() <= 1000)
            {
                $max = $scaleHint->getMax();
            }
            if($scaleHint->getMin() <= 1000)
            {
                $min = $scaleHint->getMin();
            }
            $scale = new MinMax();
            $scale->setMax(round(($max * 2004.3976484406788493955738891127)));
            $scale->setMin(round(($min * 2004.3976484406788493955738891127)));
            $wmslayer->setScale($scale);
        }

        $tempList = $this->xpath->query("./Layer", $contextElm);
        if($tempList !== null)
        {
            foreach($tempList as $item)
            {
                $subwmslayer = $this->parseLayer($wms, new WmsLayerSource(),
                                                 $item);
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

