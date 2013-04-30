<?php

namespace Mapbender\WmcBundle\Component;

/**
 * Class that Parses WMS 1.3.0 GetCapabilies Document 
 * @package Mapbender
 * @author Paul Schmidt
 */
class WmcParser110 extends WmcParser
{

    /**
     * Creates an instance
     * @param \DOMDocument $doc
     */
    public function __construct(\DOMDocument $doc)
    {
        parent::__construct($doc);
        $this->xpath->registerNamespace("cntxt",
                                        "http://www.opengis.net/context");
    }

    /**
     * Parses the GetCapabilities document
     * 
     * @return \Mapbender\WmsBundle\Entity\WmsSource
     */
    public function parse()
    {
        $wmc = array();
        $root = $this->doc->documentElement;

        $wmc["version"] = $this->getValue("./@version", $root);
        $wmc["id"] = $this->getValue("./@id", $root);
        $wmc["general"] = array();
        $genEl = $this->getValue("./cntxt:General", $root);
        $wmc["general"]["window"] = array(
            "width" => $this->getValue("./cntxt:Window/@width", $genEl),
            "height" => $this->getValue("./cntxt:Window/@height", $genEl)
        );
        $wmc["general"]["bbox"] = array(
            "srs" => $this->getValue("./cntxt:BoundingBox/@srs", $genEl),
            "minx" => $this->getValue("./cntxt:BoundingBox/@minx", $genEl),
            "miny" => $this->getValue("./cntxt:BoundingBox/@miny", $genEl),
            "maxx" => $this->getValue("./cntxt:BoundingBox/@maxx", $genEl),
            "maxy" => $this->getValue("./cntxt:BoundingBox/@maxy", $genEl)
        );

        $wmc["general"]["title"] = $this->getValue("./cntxt:Title/text()", $genEl);

        $keywordList = $this->xpath->query("./cntxt:KeywordList/cntxt:Keyword",
                                           $genEl);
        if($keywordList !== null)
        {
            foreach($keywordList as $keywordElt)
            {
                $wmc["general"]["keywords"][] = $this->getValue("./text()", $keywordElt);
            }
        }
        if($this->getValue("./cntxt:Abstract", $genEl) !== null)
        {
            $wmc["general"]["abstract"] = $this->getValue("./cntxt:Abstract/text()",
                                                   $genEl);
        }
        if($this->getValue("./cntxt:LogoURL", $genEl) !== null)
        {
            $logoEl = $this->getValue("./cntxt:LogoURL", $genEl);
            $wmc["general"]["logourl"] = array(
                "width" => $this->getValue("./@width", $logoEl),
                "height" => $this->getValue("./@height", $logoEl),
                "format" => $this->getValue("./@format", $logoEl),
                "url" => $this->getValue("./cntxt:OnlineResource/@xlink:href",
                                         $logoEl));
        }
        if($this->getValue("./cntxt:DescriptionURL", $genEl) !== null)
        {
            $descrEl = $this->getValue("./cntxt:DescriptionURL", $genEl);
            $wmc["general"]["descriptionurl"] = array(
                "format" => $this->getValue("./@format)", $descrEl),
                "url" => $this->getValue("./cntxt:OnlineResource/@xlink:href",
                                         $descrEl));
        }
        if($this->getValue("./cntxt:ContactInformation", $genEl) !== null)
        {
            $contactEl = $this->getValue("./cntxt:ContactInformation", $genEl);
            $wmc["general"]["contactinfo"] = array();
            if($this->getValue("./cntxt:ContactPersonPrimary", $contactEl) !== null)
            {
                $wmc["general"]["contactinfo"]["person"] = $this->getValue("./cntxt:ContactPersonPrimary/cntxt:ContactPerson/text()",
                                                     $contactEl);
                $wmc["general"]["contactinfo"]["organization"] = $this->getValue("./cntxt:ContactPersonPrimary/cntxt:ContactOrganization/text()",
                                                           $contactEl);
            }
            if($this->getValue("./cntxt:ContactPosition", $contactEl) !== null)
            {
                $wmc["general"]["contactinfo"]["position"] = $this->getValue("./cntxt:ContactPosition/text()",
                                                       $contactEl);
            }
            if($this->getValue("./cntxt:ContactAddress", $contactEl) !== null)
            {
                $addrEl = $this->getValue("./cntxt:ContactAddress", $contactEl);
                $wmc["general"]["contactinfo"]["address"] = array(
                    "type" => $this->getValue("./cntxt:AddressType/text()",
                                              $addrEl),
                    "address" => $this->getValue("./cntxt:Address/text()",
                                                 $addrEl),
                    "city" => $this->getValue("./cntxt:City/text()", $addrEl),
                    "state" => $this->getValue("./cntxt:StateOrProvince/text()",
                                               $addrEl),
                    "postcode" => $this->getValue("./cntxt:PostCode/text()",
                                                  $addrEl),
                    "country" => $this->getValue("./cntxt:Country/text()",
                                                 $addrEl)
                );
            }
            if($this->getValue("./cntxt:ContactVoiceTelephone", $contactEl) !== null)
            {
                $wmc["general"]["contactinfo"]["phone"] = $this->getValue("./cntxt:ContactVoiceTelephone/text()",
                                                    $contactEl);
            }
            if($this->getValue("./cntxt:ContactFacsimileTelephone", $contactEl) !== null)
            {
                $wmc["general"]["contactinfo"]["fax"] = $this->getValue("./cntxt:ContactFacsimileTelephone/text()",
                                                  $contactEl);
            }
            if($this->getValue("./cntxt:ContactElectronicMailAddress",
                               $contactEl) !== null)
            {
                $wmc["general"]["contactinfo"]["email"] = $this->getValue("./cntxt:ContactElectronicMailAddress/text()",
                                                    $contactEl);
            }
        }
        $layerList = $this->xpath->query("./cntxt:LayerList/cntxt:Layer", $root);
        $wmc["layerlist"] = array();
//        $layerlist = $wmc["layerlist"];
        foreach($layerList as $layerElm)
        {
            $wmc["layerlist"][] = $this->parseLayer($layerElm);
        }
        return $wmc;
    }

    /**
     * Parses the Service section of the GetCapabilities document
     * 
     * @param \Mapbender\WmsBundle\Entity\WmsSource $wmc the WmsSource
     * @param \DOMElement $contextElm the element to use as context for
     * the Service section
     */
    private function parseLayer(\DOMElement $layerElm)
    {
        $layer = array(
            "queryable" => $this->getValue("./@queryable", $layerElm),
            "hidden" => $this->getValue("./@hidden", $layerElm),
            "server" => array(
                "service" => $this->getValue("./cntxt:Server/@service",
                                             $layerElm),
                "version" => $this->getValue("./cntxt:Server/@version",
                                             $layerElm),
                "title" => $this->getValue("./cntxt:Server/@title", $layerElm),
                "url" => $this->getValue("./cntxt:Server/cntxt:OnlineResource/@xlink:href",
                                         $layerElm)),
            "name" => $this->getValue("./cntxt:Name/text()", $layerElm),
            "title" => $this->getValue("./cntxt:Title/text()", $layerElm),
        );
        if($this->getValue("./cntxt:Abstract", $layerElm) !== null)
        {
            $layer["abstract"] = $this->getValue("./cntxt:Abstract/text()",
                                                 $layerElm);
        }
        if($this->getValue("./SRS", $layerElm) !== null)
        {
            $layer["srs"] = $this->getValue("./cntxt:SRS/text()", $layerElm);
        }
        $formatList = $this->xpath->query("./cntxt:FormatList/cntxt:Format",
                                          $layerElm);
        $layer["formats"] = array();
        foreach($formatList as $formatElm)
        {
            $layer["formats"][] = array(
                "current" => $this->getValue("./@current", $formatElm),
                "format" => $this->getValue("./text()", $formatElm),
            );
        }

        $styleList = $this->xpath->query("./cntxt:StyleList/cntxt:Style",
                                         $layerElm);
        $layer["styles"] = array();
        foreach($formatList as $styleElm)
        {
            $style = array(
                "current" => $this->getValue("./@current", $styleElm),
                "name" => $this->getValue("./cntxt:Name/text()", $styleElm),
                "title" => $this->getValue("./cntxt:Title/text()", $styleElm),
            );
            if($this->getValue("./LegendURL", $styleElm) !== null)
            {
                $style["legend"] = array(
                    "width" => $this->getValue("./cntxt:LegendURL/@width",
                                               $styleElm),
                    "height" => $this->getValue("./cntxt:LegendURL/@height",
                                                $styleElm),
                    "url" => $this->getValue("./cntxt:LegendURL/cntxt:OnlineResource/@xlink:href",
                                             $styleElm)
                );
            }
            $layer["styles"][] = $style;
        }

        return $layer;
    }

}

