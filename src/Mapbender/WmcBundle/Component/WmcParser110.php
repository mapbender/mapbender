<?php

namespace Mapbender\WmcBundle\Component;

use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\Size;
use Mapbender\CoreBundle\Component\StateHandler;
use Mapbender\CoreBundle\Entity\State;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\MinMax;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Component\RequestInformation;
use Mapbender\WmsBundle\Component\Style;
use Mapbender\WmsBundle\Component\WmsInstanceConfiguration;
use Mapbender\WmsBundle\Component\WmsInstanceConfigurationOptions;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
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
        $this->xpath->registerNamespace("ol", "http://openlayers.org/context");
//        $this->xpath->registerNamespace("mb3wmc", "http://mapbender3.org/wmc");
        $this->xpath->registerNamespace("mapbender", "http://mapbender3.org/wmc");
        $this->xpath->registerNamespace("mb3", "http://mapbender3.org");
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
        $root = $this->doc->documentElement;
        $wmc->setWmcid($this->getValue("./@id", $root));
        $wmc->setVersion($this->getValue("./@version", $root));
        $genEl = $this->getValue("./cntxt:General", $root);
        $stateHandler->setWindow(new Size(
                        intval($this->getValue("./cntxt:Window/@width", $genEl)),
                        intval($this->getValue("./cntxt:Window/@height", $genEl))));
        $stateHandler->setExtent($this->getBoundingBox(array("./cntxt:BoundingBox"),
                                                       $genEl, null));
        $stateHandler->setMaxextent($this->getBoundingBox(
                        array("./cntxt:Extension/mb3:maxExtent",
                    "./cntxt:Extension/ol:maxExtent"), $genEl,
                        $stateHandler->getExtent()->srs));

        $stateHandler->setName($this->getValue("./cntxt:Title/text()", $genEl));
        $keywordList = $this->xpath->query("./cntxt:KeywordList/cntxt:Keyword",
                                           $genEl);
        if($keywordList !== null)
        {
            $keywords = array();
            foreach($keywordList as $keywordElt)
            {
                $keywords[] = $this->getValue("./text()", $keywordElt);
            }
            $wmc->setKeywords($keywords);
        }
        if($this->getValue("./cntxt:Abstract", $genEl) !== null)
        {
            $wmc->setAbstract($this->getValue("./cntxt:Abstract/text()", $genEl));
        }
        $logoEl = $this->getValue("./cntxt:LogoURL", $genEl);
        if($logoEl !== null)
        {
            $wmc->setLogourl(new LegendUrl(
                            new OnlineResource(
                                    $this->getValue("./@format", $logoEl),
                                    $this->getValue("./cntxt:OnlineResource/@xlink:href",
                                                    $logoEl)),
                            intval($this->getValue("./@width", $logoEl)),
                            intval($this->getValue("./@height", $logoEl))));
        }
        $descrEl = $this->getValue("./cntxt:DescriptionURL", $genEl);
        if($descrEl !== null)
        {
            $wmc->setDescriptionurl(new OnlineResource(
                            $this->getValue("./@format)", $descrEl),
                            $this->getValue("./cntxt:OnlineResource/@xlink:href",
                                            $descrEl)));
        }
        $contactEl = $this->getValue("./cntxt:ContactInformation", $genEl);
        if($contactEl !== null)
        {
            $contact = new Contact();
            $contact->setPerson($this->getValue("./cntxt:ContactPersonPrimary/cntxt:ContactPerson/text()", $contactEl));
            $contact->setOrganization($this->getValue("./cntxt:ContactPersonPrimary/cntxt:ContactOrganization/text()", $contactEl));
            $contact->setPosition($this->getValue("../cntxt:ContactPosition/text()", $contactEl));
            
            $addrEl = $this->getValue("./cntxt:ContactAddress", $contactEl);
            if($addrEl !== null)
            {
                $contact->setAddressType($this->getValue("./cntxt:AddressType/text()", $addrEl));
                $contact->setAddress($this->getValue("./cntxt:Address/text()", $addrEl));
                $contact->setAddressCity($this->getValue("./cntxt:City/text()", $addrEl));
                $contact->setAddressStateOrProvince($this->getValue("./cntxt:StateOrProvince/text()", $addrEl));
                $contact->setAddressPostCode($this->getValue("./cntxt:PostCode/text()", $addrEl));
                $contact->setAddressCountry($this->getValue("./cntxt:Country/text()", $addrEl));
            }

            $contact->setVoiceTelephone($this->getValue("./cntxt:ContactVoiceTelephone/text()", $contactEl));
            $contact->setFacsimileTelephone($this->getValue("./cntxt:ContactFacsimileTelephone/text()", $contactEl));
            $contact->setElectronicMailAddress($this->getValue("./cntxt:ContactElectronicMailAddress/text()", $contactEl));

            $wmc->setContact($contact);
        }
        $layerList = $this->xpath->query("./cntxt:LayerList/cntxt:Layer", $root);
        foreach($layerList as $layerElm)
        {
            $stateHandler->addSource($this->parseLayer($layerElm,
                                                       $stateHandler->getExtent()->srs));
        }
        $wmc->setState($stateHandler->generateState());
        return $wmc;
    }

    /**
     * Parses the Service section of the GetCapabilities document
     * 
     * @param \Mapbender\WmsBundle\Entity\WmsSource $wmc the WmsSource
     * @param \DOMElement $contextElm the element to use as context for
     * the Service section
     */
    private function parseLayer(\DOMElement $layerElm, $srs)
    {
        $wmsinst = new WmsInstance();
        $wms = new WmsSource();
        $id = round(microtime(true) * 1000);
        $queryable = $this->getValue("./@queryable", $layerElm);
        $wmsinst->setVisible(!(bool) $this->getValue("./@hidden", $layerElm));
        $formats = array();
        $formatList = $this->xpath->query("./cntxt:FormatList/cntxt:Format",
                                          $layerElm);
        foreach($formatList as $formatElm)
        {
            $formats[] = $this->getValue("./text()", $formatElm);
            $current = (bool) $this->getValue("./@current", $formatElm);
            if($current)
                    $wmsinst->setFormat($this->getValue("./text()", $formatElm));
        }
        $wms->setVersion($this->getValue("./cntxt:Server/@version", $layerElm));
        $wms->setGetMap(new RequestInformation($this->getValue("./cntxt:Server/cntxt:OnlineResource/@xlink:href",
                                                               $layerElm), null, $formats));

        $srsList = $this->xpath->query("./cntxt:SRS", $layerElm);
        $srses = array();
        foreach($srsList as $srsElm)
        {
            $srses[] = $this->getValue("./text()", $srsElm);
        }

        $styleList = $this->xpath->query("./cntxt:StyleList/cntxt:Style",
                                         $layerElm);
        $styles = array();
        foreach($styleList as $styleElm)
        {
            $current = (bool) $this->getValue("./@current", $styleElm);
            if($current)
            {
                
            }
            $style = new Style();
            $style->setName($this->getValue("./cntxt:Name/text()", $styleElm));
            $style->setTitle($this->getValue("./cntxt:Title/text()", $styleElm));
            $style->setAbstract($this->getValue("./cntxt:Abstract/text()",
                                                $styleElm));
            $legendUrlEl = $this->getValue("./cntxt:LegendURL", $styleElm);
            if($legendUrlEl !== null)
            {
                $legendUrl = new LegendUrl();
                $legendUrl->setWidth($this->getValue("./@width", $legendUrlEl));
                $legendUrl->setHeight($this->getValue("./@height", $legendUrlEl));
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat($this->getValue("./format",
                                                           $legendUrlEl));
                $onlineResource->setHref($this->getValue("./cntxt:OnlineResource/@xlink:href",
                                                         $legendUrlEl));
                $legendUrl->setOnlineResource($onlineResource);
                $style->setLegendUrl($legendUrl);
            }
            //@ TODO cntxt:Style/cntxt:SLD
            $styles[] = $style;
        }


        $minScaleEl = $this->getValue("./sld:MinScaleDenominator", $layerElm);
        $maxScaleEl = $this->getValue("./sld:MaxScaleDenominator", $layerElm);
        $scale = null;
        if($minScaleEl !== null || $maxScaleEl !== null)
        {
            $scale = new MinMax();
            $min = $this->getValue("./sld:MinScaleDenominator/text()", $layerElm);
            $scale->setMin($min !== null ? floatval($min) : null);
            $max = $this->getValue("./sld:MaxScaleDenominator/text()", $layerElm);
            $scale->setMax($max !== null ? floatval($max) : null);
        }

        $layerconfig = array();
        $extensionEl = $this->getValue("./cntxt:Extension", $layerElm);
        $layerconfig["maxExtent"] = $this->getBoundingBox(
                array("./mb:maxExtent", "./ol:maxExtent"),
                $this->getValue("./cntxt:Extension", $extensionEl), $srs);
        $layerconfig["tiled"] = $this->findFirstValue(array("./mb3:tiled"),
                                                      $extensionEl);
        $wms->setName($this->getValue("./cntxt:Name/text()", $layerElm));
        $wmsinst->setId(intval($id))
                ->setTitle($this->getValue("./cntxt:Title/text()", $layerElm))
                ->setTransparency((bool) $this->findFirstValue(
                                array("./mb3:transparency/text()", "./ol:transparent/text()"),
                                $extensionEl, true))
                ->setOpacity($this->findFirstValue(array("./mb3:opacity", "./ol:opacity"),
                                                   $extensionEl, 1));
        $wmsinst->setTiled((bool) $this->findFirstValue(array("./mb3:tiled"),
                                                        $extensionEl, false));
//                ->setConfiguration($layerDefinition);



        $wmsinst->setSource($wms);
        $wmsconf = new WmsInstanceConfiguration();
        $wmsconf->setType(strtolower($wmsinst->getType()));
        $wmsconf->setTitle($wmsinst->getTitle());
        $wmsconf->setIsBaseSource(false);

        $options = new WmsInstanceConfigurationOptions();
        $options->setUrl($wms->getGetMap()->getHttpGet())
                ->setVisible($wmsinst->getVisible())
                ->setFormat($wmsinst->getFormat())
//                ->setInfoformat($this->infoformat)
                ->setTransparency($wmsinst->getTransparency())
                ->setOpacity($wmsinst->getOpacity())
                ->setTiled($wmsinst->getTiled());
        $wmsconf->setOptions($options);

        $layerList = $this->findFirstList(
                array("mb3:layers/mb3:layer", "mapbender:layers/mapbender:layer"),
                $extensionEl);
        if($layerList->length > 0)
        {
            $num = 0;
            $rootInst = new WmsInstanceLayer();
            $rootInst->setTitle($wmsinst->getTitle())
                    ->setId($wmsinst->getId() . "_" . $num)
                    ->setPriority($num)
                    ->setWmslayersource(new WmsLayerSource())
                    ->setWmsInstance($wmsinst);
            $rootInst->setToggle(false);
            $rootInst->setAllowtoggle(true);
            foreach($layerList as $layerElm)
            {
                $num++;
                $layerInst = new WmsInstanceLayer();
                $layersource = new WmsLayerSource();
                $layersource->setName($this->findFirstValue(
                                array("./@name"), $layerElm, $num));
                $legendurl = $this->getBoundingBox(array("./@legendUrl", "./@legend"),
                                                   $layerElm, null);
                if($legendurl !== null)
                {
                    $style = new Style();
                    $style->setName(null);
                    $style->setTitle(null);
                    $style->setAbstract(null);
                    $legendUrl = new LegendUrl();
                    $legendUrl->setWidth(null);
                    $legendUrl->setHeight(null);
                    $onlineResource = new OnlineResource();
                    $onlineResource->setFormat(null);
                    $onlineResource->setHref($legendurl);
                    $legendUrl->setOnlineResource($onlineResource);
                    $style->setLegendUrl($legendUrl);
                    $layersource->addStyle($style);
                }
                $layerInst->setTitle($this->findFirstValue(
                                        array("./@title"), $layerElm, $num))
                        ->setParent($rootInst)
                        ->setId($wmsinst->getId() . "_" . $num)
                        ->setPriority($num)
                        ->setInfo($this->findFirstValue(
                                        array("./@queryable"), $layerElm, false))
                        ->setWmslayersource($layersource)
                        ->setWmsInstance($wmsinst);
                $rootInst->addSublayer($layerInst);
                $wmsinst->addLayer($layerInst);
            }
            $children = array($wmsinst->generateLayersConfiguration($rootInst));
            $wmsconf->setChildren($children);
            return array(
                'type' => $wmsinst->getType(),
                'title' => $wmsinst->getTitle(),
                'id' => $wmsinst->getId(),
                'configuration' => $wmsconf->toArray());
        } else
        {
            //@TODO ...
            return null;
        }
    }

    private function getBoundingBox($xpathStrArr, $contextElm, $defSrs)
    {
        if($contextElm !== null)
        {
            $extentEl = $this->findFirstValue($xpathStrArr, $contextElm);
            if($extentEl !== null)
            {
                if($this->getValue("./@SRS", $extentEl) !== null)
                        $srs = $this->getValue("./@SRS", $extentEl);
                else if($this->getValue("./@srs", $extentEl) !== null)
                        $srs = $this->getValue("./@srs", $extentEl);
                else $srs = $defSrs;
                return new BoundingBox($srs,
                                floatval($this->getValue("./@minx", $extentEl)),
                                floatval($this->getValue("./@miny", $extentEl)),
                                floatval($this->getValue("./@maxx", $extentEl)),
                                floatval($this->getValue("./@maxy", $extentEl)));
            } else
            {
                return null;
            }
        } else
        {
            return null;
        }
    }

    private function findFirstValue($xpathStrArr, $contextElm,
            $defaultValue = null)
    {
        if($contextElm !== null)
        {
            foreach($xpathStrArr as $xpathStr)
            {
                $extentEl = $this->getValue($xpathStr, $contextElm);
                if($extentEl !== null)
                {
                    return $extentEl;
                }
            }
            if($defaultValue !== null)
            {
                return $defaultValue;
            } else
            {
                return null;
            }
        } else
        {
            return null;
        }
    }

    private function findFirstList($xpathStrArr, $contextElm)
    {
        if($contextElm !== null)
        {
            foreach($xpathStrArr as $xpathStr)
            {
                $extentList = $this->xpath->query($xpathStr, $contextElm);
                if($extentList !== null && $extentList->length > 0)
                {
                    return $extentList;
                }
            }
            return null;
        } else
        {
            return null;
        }
    }

}

