<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\WmsBundle\Component\Exception\ParsingException;

use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\WmsBundle\Entity\RequestInformation;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\CoreBundle\Component\BoundingBox;

/**
* Class that Parses WMS 1.3.0 GetCapabilies Document 
* @package Mapbender
* @author Karim Malhas <karim@malhas.de>
*/
class Wms130CapabilitiesParser extends WmsCapabilitiesParser {
    
    private $servNsPrefix = "ms";
    
    public function __construct(\DOMDocument $doc){
        parent::__construct($doc);

        foreach( $this->xpath->query('namespace::*', $this->doc->documentElement) as $node ) {
            $nsPrefix = $node->prefix;
            $nsUri = $node->nodeValue;
            if($nsPrefix == "" && $nsUri == "http://www.opengis.net/wms"){
                $nsPrefix = "wms";
            }
            if($nsPrefix != "wms" && $nsPrefix != "xsi" &&  $nsPrefix != "xlink" &&  $nsPrefix != "sld" &&  $nsPrefix != "xml"){
                $this->servNsPrefix = $nsPrefix;
            }
            $this->xpath->registerNamespace($nsPrefix , $nsUri);
        }
    }
    
    public function parse(){
        $wms  = new WmsSource();
        $root = $this->doc->documentElement;

        $this->parseService($wms, $this->getValue("./wms:Service", $root));
        $this->parseCapabilityRequest($wms, $this->getValue("./wms:Capability/wms:Request", $root));
        $this->parseCapabilityException($wms, $this->getValue("./wms:Capability/wms:Exception", $root));
        $this->parseUserDefinedSymbolization($wms, $this->getValue("./wms:Capability/sld:UserDefinedSymbolization", $root));
        $rootlayer = new WmsLayerSource();
        $layer = $this->parseLayer($wms, $rootlayer, $this->getValue("./wms:Capability/wms:Layer", $root));
        $wms->addLayer($layer);
        return $wms;
    }
    
    private function parseService(WmsSource $wms, \DOMElement $contextElm){
        $wms->setName($this->getValue("./wms:Name/text()", $contextElm));
        $wms->setTitle($this->getValue("./wms:Title/text()", $contextElm));
        $wms->setDescription($this->getValue("./wms:Abstract/text()", $contextElm));
        $wms->setOnlineResource($this->getValue("./wms:OnlineResource/@xlink:href", $contextElm));
        
        $wms->setFees($this->getValue("./wms:Fees/text()", $contextElm));
        $wms->setAccessConstraints($this->getValue("./wms:AccessConstraints/text()", $contextElm));
        $maxWidth = intval($this->getValue("./wms:MaxWidth/text()", $contextElm));
        if($maxWidth > 0){
            $wms->setMaxWidth(intval($maxWidth));
        }
        $maxHeight = intval($this->getValue("./wms:MaxHeight/text()", $contextElm));
        if($maxHeight > 0){
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
    
    private function parseCapabilityRequest(WmsSource $wms, \DOMElement $contextElm){
        $tempEl = $this->getValue("./wms:GetCapabilities", $contextElm);
        if($tempEl !== null) {
            $getCapabilities = $this->parseOperationRequestInformation($tempEl);
            $wms->setGetCapabilities($getCapabilities);
        }
    
        $getMap =  $this->parseOperationRequestInformation($tempEl);
        if($tempEl !== null) {
            $tempEl = $this->getValue("./wms:GetMap", $contextElm);
            $wms->setGetMap($getMap);
        }
    
        $tempEl = $this->getValue("./wms:GetFeatureInfo", $contextElm);
        if($tempEl !== null) {
            $getFeatureInfo =  $this->parseOperationRequestInformation($tempEl);
            $wms->setGetFeatureInfo($getFeatureInfo);
        }
        
        $tempEl = $this->getValue("./sld:GetLegendGraphic", $contextElm);
        if($tempEl !== null) {
            $getLegendGraphic = $this->parseOperationRequestInformation($tempEl);
            $wms->setGetLegendGraphic($getLegendGraphic);
        }
        
        $tempEl = $this->getValue("./sld:DescribeLayer", $contextElm);
        if($tempEl !== null) {
            $describeLayer =  $this->parseOperationRequestInformation($tempEl);
            $wms->setDescribeLayer($describeLayer);
        }
        
        $tempEl = $this->getValue("./".$this->servNsPrefix.":GetStyles", $contextElm);
        if($tempEl !== null) {
            $getStyles = $this->parseOperationRequestInformation($tempEl);
            $wms->setGetStyles($getStyles);
        }
        
        $tempEl = $this->getValue("./".$this->servNsPrefix.":PutStyles", $contextElm);
        if($tempEl !== null) {
            $putStyles =  $this->parseOperationRequestInformation($tempEl);
            $wms->setPutStyles($putStyles);
        }
    }
    
    private function parseOperationRequestInformation(\DOMElement $contextElm){
        $requestImformation = new RequestInformation();
        $tempList = $this->xpath->query("./wms:Format", $contextElm);
        if($tempList !== null){
            foreach ($tempList as $item) {
                $requestImformation->addFormat($this->getValue("./text()", $item));
            }
        }
        $requestImformation->setHttpGet($this->getValue(
                "./wms:DCPType/wms:HTTP/wms:Get/wms:OnlineResource/@xlink:href",
                $contextElm));
        $requestImformation->setHttpPost($this->getValue(
                "./wms:DCPType/wms:HTTP/wms:Post/wms:OnlineResource/@xlink:href",
                $contextElm));
        
        return $requestImformation;
    }
    
    private function parseCapabilityException(WmsSource $wms, \DOMElement $contextElm){
        $tempList = $this->xpath->query("./wms:Format", $contextElm);
        if($tempList !== null){
            foreach ($tempList as $item) {
                $wms->addExceptionFormat($this->getValue("./text()", $item));
            }
        }
    }
    
    private function parseUserDefinedSymbolization(WmsSource $wms, \DOMElement $contextElm){
        if($contextElm !== null){
            $wms->setSupportSld($this->getValue("./@SupportSLD", $contextElm));
            $wms->setUserLayer($this->getValue("./@UserLayer", $contextElm));
            $wms->setUserStyle($this->getValue("./@UserStyle", $contextElm));
            $wms->setRemoteWfs($this->getValue("./@RemoteWFS", $contextElm));
            $wms->setInlineFeature($this->getValue("./@InlineFeature", $contextElm));
            $wms->setRemoteWcs($this->getValue("./@RemoteWCS", $contextElm));
        }
    }
    private function parseLayer(WmsSource $wms, WmsLayerSource $wmslayer, \DOMElement $contextElm){
        $wmslayer->setName($this->getValue("./wms:Name/text()", $contextElm));
        $wmslayer->setTitle($this->getValue("./wms:Title/text()", $contextElm));
        $wmslayer->setAbstract($this->getValue("./wms:Abstract/text()", $contextElm));
        //@TODO KeywordList/Keyword
        $tempList = $this->xpath->query("./wms:CRS", $contextElm);
        if($tempList !== null){
            foreach ($tempList as $item) {
                $wmslayer->addSrs($this->getValue("./text()", $item));
            }
        }
        
        $latlonBounds = new BoundingBox();
        $latlonBounds->setSrs("EPSG:4326");
        $latlonBounds->setMinx($this->getValue("./wms:EX_GeographicBoundingBox/wms:westBoundLongitude/text()", $contextElm));
        $latlonBounds->setMiny($this->getValue("./wms:EX_GeographicBoundingBox/wms:southBoundLatitude/text()", $contextElm));
        $latlonBounds->setMaxx($this->getValue("./wms:EX_GeographicBoundingBox/wms:eastBoundLongitude/text()", $contextElm));
        $latlonBounds->setMaxy($this->getValue("./wms:EX_GeographicBoundingBox/wms:northBoundLatitude/text()", $contextElm));
        $wmslayer->setLatlonBounds($latlonBounds);
        
        $tempList = $this->xpath->query("./wms:BoundingBox", $contextElm);
        if($tempList !== null){
            foreach ($tempList as $item) {
                $bbox = new BoundingBox();
                $bbox->setSrs($this->getValue("./@CRS", $item));
                $bbox->setMinx($this->getValue("./@minx", $item));
                $bbox->setMiny($this->getValue("./@miny", $item));
                $bbox->setMaxx($this->getValue("./@maxx", $item));
                $bbox->setMaxy($this->getValue("./@maxy", $item));
                $wmslayer->addBoundingBox($bbox);
            }
        }
        $wmslayer->setMinScale(floatval($this->getValue("./wms:MinScaleDenominator/text()", $contextElm)));
        $wmslayer->setMaxScale(floatval($this->getValue("./wms:MaxScaleDenominator/text()", $contextElm)));
        
        $tempList = $this->xpath->query("./wms:Style", $contextElm);
        if($tempList !== null){
            foreach ($tempList as $item) {
                $style = new Style();
                $style->setName($this->getValue("./wms:Name/text()", $item));
                $style->setTitle($this->getValue("./wms:Title/text()", $item));
                $style->setAbstract($this->getValue("./wms:Abstract/text()", $item));
                $legendUrl = new LegendUrl();
                $legendUrl->setWidth($this->getValue("./wms:LegendURL/@width", $item));
                $legendUrl->setHeight($this->getValue("./wms:LegendURL/@height", $item));
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat($this->getValue("./wms:LegendURL/wms:Format/text()", $item));
                $onlineResource->setHref($this->getValue("./wms:LegendURL/wms:OnlineResource/xlink:href", $item));
                $legendUrl->setOnlineResource($onlineResource);
                $style->setLegendUrl($legendUrl);
                $wmslayer->addStyle($style);
            }
        }
        $tempList = $this->xpath->query("./wms:Layer", $contextElm);
        if($tempList !== null){
            foreach ($tempList as $item) {
                $subwmslayer = $this->parseLayer($wms, new WmsLayerSource(), $item);
                $subwmslayer->setParent($wmslayer);
                $wms->addLayer($subwmslayer);
            }
        }
        return $wmslayer;
//        <Style>
//                    <Name>default</Name>
//                    <Title>default</Title>
//                    <LegendURL width="125" height="71">
//                        <Format>image/png</Format>
//                        <OnlineResource xmlns:xlink="http://www.w3.org/1999/xlink" xlink:type="simple" xlink:href="http://wms.wheregroup.com/cgi-bin/mapserv?map=/data/umn/germany/germany.map&amp;version=1.3.0&amp;service=WMS&amp;request=GetLegendGraphic&amp;sld_version=1.1.0&amp;layer=Topographie&amp;format=image/png&amp;STYLE=default"/>
//                    </LegendURL>
//                </Style>
    }
}

