<?php

namespace Mapbender\WmcBundle\Component;

use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\StateHandler;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Component\Size;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsSource;

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
        $wmc = new Wmc();
        $stateHandler = new StateHandler();
        $wmcArr = array();
        $root = $this->doc->documentElement;

//        $wmcArr["version"] = $this->getValue("./@version", $root);
//        $wmcArr["id"] = $this->getValue("./@id", $root);
        $wmc->setWmcid($this->getValue("./@id", $root));
//        $wmcArr["general"] = array();
        $genEl = $this->getValue("./cntxt:General", $root);
//        $wmcArr["general"]["window"] = array(
//            "width" => $this->getValue("./cntxt:Window/@width", $genEl),
//            "height" => $this->getValue("./cntxt:Window/@height", $genEl)
//        );
        $stateHandler->setWindow(new Size(
                        intval($this->getValue("./cntxt:Window/@width", $genEl)),
                        intval($this->getValue("./cntxt:Window/@height", $genEl))));
//        $wmcArr["general"]["bbox"] = array(
//            "srs" => $this->getValue("./cntxt:BoundingBox/@srs", $genEl),
//            "minx" => $this->getValue("./cntxt:BoundingBox/@minx", $genEl),
//            "miny" => $this->getValue("./cntxt:BoundingBox/@miny", $genEl),
//            "maxx" => $this->getValue("./cntxt:BoundingBox/@maxx", $genEl),
//            "maxy" => $this->getValue("./cntxt:BoundingBox/@maxy", $genEl)
//        );

        $stateHandler->setExtent(new BoundingBox(
                        $this->getValue("./cntxt:BoundingBox/@srs", $genEl),
                        floatval($this->getValue("./cntxt:BoundingBox/@minx",
                                                 $genEl)),
                        floatval($this->getValue("./cntxt:BoundingBox/@miny",
                                                 $genEl)),
                        floatval($this->getValue("./cntxt:BoundingBox/@maxx",
                                                 $genEl)),
                        floatval($this->getValue("./cntxt:BoundingBox/@maxy",
                                                 $genEl))));

//        $wmcArr["general"]["title"] = $this->getValue("./cntxt:Title/text()",
//                                                      $genEl);
        $stateHandler->setName($this->getValue("./cntxt:Title/text()", $genEl));
        $keywordList = $this->xpath->query("./cntxt:KeywordList/cntxt:Keyword",
                                           $genEl);
        if($keywordList !== null)
        {
            $keywords = array();
            foreach($keywordList as $keywordElt)
            {
//                $wmcArr["general"]["keywords"][] = $this->getValue("./text()",
//                                                                   $keywordElt);
                $keywords[] = $this->getValue("./text()", $keywordElt);
            }
            $wmc->setKeywords($keywords);
        }
        if($this->getValue("./cntxt:Abstract", $genEl) !== null)
        {
//            $wmcArr["general"]["abstract"] = $this->getValue("./cntxt:Abstract/text()",
//                                                             $genEl);
            $wmc->setAbstract($this->getValue("./cntxt:Abstract/text()",
                                                             $genEl));
        }
        if($this->getValue("./cntxt:LogoURL", $genEl) !== null)
        {
            $logoEl = $this->getValue("./cntxt:LogoURL", $genEl);
//            $wmcArr["general"]["logourl"] = array(
//                "width" => $this->getValue("./@width", $logoEl),
//                "height" => $this->getValue("./@height", $logoEl),
//                "format" => $this->getValue("./@format", $logoEl),
//                "url" => $this->getValue("./cntxt:OnlineResource/@xlink:href",
//                                         $logoEl));
            
            $wmc->setLogourl(new LegendUrl(
                    new OnlineResource(
                            $this->getValue("./@format", $logoEl),
                            $this->getValue("./cntxt:OnlineResource/@xlink:href", $logoEl)),
                    intval($this->getValue("./@width", $logoEl)),
                    intval($this->getValue("./@height", $logoEl))));
        }
        if($this->getValue("./cntxt:DescriptionURL", $genEl) !== null)
        {
            $descrEl = $this->getValue("./cntxt:DescriptionURL", $genEl);
//            $wmcArr["general"]["descriptionurl"] = array(
//                "format" => $this->getValue("./@format)", $descrEl),
//                "url" => $this->getValue("./cntxt:OnlineResource/@xlink:href",
//                                         $descrEl));
            $wmc->setDescriptionurl(new OnlineResource(
                    $this->getValue("./@format)", $descrEl),
                    $this->getValue("./cntxt:OnlineResource/@xlink:href", $descrEl)));
        }
        if($this->getValue("./cntxt:ContactInformation", $genEl) !== null)
        {
            $contactEl = $this->getValue("./cntxt:ContactInformation", $genEl);
            $wmcArr["general"]["contactinfo"] = array();
            if($this->getValue("./cntxt:ContactPersonPrimary", $contactEl) !== null)
            {
                $wmcArr["general"]["contactinfo"]["person"] = $this->getValue("./cntxt:ContactPersonPrimary/cntxt:ContactPerson/text()",
                                                                              $contactEl);
                $wmcArr["general"]["contactinfo"]["organization"] = $this->getValue("./cntxt:ContactPersonPrimary/cntxt:ContactOrganization/text()",
                                                                                    $contactEl);
            }
            if($this->getValue("./cntxt:ContactPosition", $contactEl) !== null)
            {
                $wmcArr["general"]["contactinfo"]["position"] = $this->getValue("./cntxt:ContactPosition/text()",
                                                                                $contactEl);
            }
            if($this->getValue("./cntxt:ContactAddress", $contactEl) !== null)
            {
                $addrEl = $this->getValue("./cntxt:ContactAddress", $contactEl);
                $wmcArr["general"]["contactinfo"]["address"] = array(
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
                $wmcArr["general"]["contactinfo"]["phone"] = $this->getValue("./cntxt:ContactVoiceTelephone/text()",
                                                                             $contactEl);
            }
            if($this->getValue("./cntxt:ContactFacsimileTelephone", $contactEl) !== null)
            {
                $wmcArr["general"]["contactinfo"]["fax"] = $this->getValue("./cntxt:ContactFacsimileTelephone/text()",
                                                                           $contactEl);
            }
            if($this->getValue("./cntxt:ContactElectronicMailAddress",
                               $contactEl) !== null)
            {
                $wmcArr["general"]["contactinfo"]["email"] = $this->getValue("./cntxt:ContactElectronicMailAddress/text()",
                                                                             $contactEl);
            }
        }
        $layerList = $this->xpath->query("./cntxt:LayerList/cntxt:Layer", $root);
        $wmcArr["layerlist"] = array();
//        $layerlist = $wmc["layerlist"];
        foreach($layerList as $layerElm)
        {
            $wmcArr["layerlist"][] = $this->parseLayer($layerElm);
        }
        return $wmcArr;
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
        $wmsinst = new WmsInstance();
        $wmsinst->setId($id)
//                ->setTitle($layerDefinition['title'])
//                ->setWeight($weight++)
//                ->setLayerset($layerset)
//                ->setProxy(!isset($layerDefinition['proxy']) ? false : $layerDefinition['proxy'])
                ->setVisible(!isset($layerDefinition['visible']) ? true : $layerDefinition['visible'])
                ->setFormat(!isset($layerDefinition['format']) ? true : $layerDefinition['format'])
                ->setInfoformat(!isset($layerDefinition['info_format']) ? null : $layerDefinition['info_format'])
                ->setTransparency(!isset($layerDefinition['transparent']) ? true : $layerDefinition['transparent'])
                ->setOpacity(!isset($layerDefinition['opacity']) ? 100 : $layerDefinition['opacity'])
                ->setTiled(!isset($layerDefinition['tiled']) ? false : $layerDefinition['tiled'])
                ->setConfiguration($layerDefinition);
        
        
        
        $wmsinst->setSource(new WmsSource());
        $wmsconf = new WmsInstanceConfiguration();
        $wmsconf->setType(strtolower($wmsinst->getType()));
        $wmsconf->setTitle($wmsinst->title);
        $wmsconf->setIsBaseSource(true);
        
        $options = new WmsInstanceConfigurationOptions();
        $options->setUrl($this->configuration["url"])
                ->setProxy($this->proxy)
                ->setVisible($this->visible)
                ->setFormat($this->getFormat())
                ->setInfoformat($this->infoformat)
                ->setTransparency($this->transparency)
                ->setOpacity($this->opacity / 100)
                ->setTiled($this->tiled);
        $wmsconf->setOptions($options);
        
        
        
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

