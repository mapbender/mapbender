<?php

namespace Mapbender\WmcBundle\Component;

use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Component\Size;
use Mapbender\CoreBundle\Component\StateHandler;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\WmcBundle\Entity\Wmc;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\MinMax;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Component\RequestInformation;
use Mapbender\WmsBundle\Component\Style;
use Mapbender\WmsBundle\Component\WmsInstanceConfiguration;
use Mapbender\WmsBundle\Component\WmsInstanceConfigurationOptions;
use Mapbender\WmsBundle\Component\WmsInstanceLayerEntityHandler;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class that Parses WMC 1.1.0 WMC Document
 * @package Mapbender
 * @author Paul Schmidt
 */
class WmcParser110 extends WmcParser
{

    /**
     * @inheritdoc
     */
    public function __construct(ContainerInterface $contatiner, \DOMDocument $doc)
    {
        parent::__construct($contatiner, $doc);
        $this->xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
        $this->xpath->registerNamespace("cntxt", "http://www.opengis.net/context");
        $this->xpath->registerNamespace("sld", "http://www.opengis.net/sld");
        $this->xpath->registerNamespace("mb3", "http://mapbender3.org");
        $this->xpath->registerNamespace("mb", "http://mapbender.org");
        $this->xpath->registerNamespace("ol", "http://openlayers.org/context");
    }

    /**
     * @inheritdoc
     */
    public function parse($infoFormat = "text/html")
    {
        $wmc = new Wmc();
        $stateHandler = new StateHandler();
        $root = $this->doc->documentElement;
        $id = $this->getValue("./@id", $root);
        $wmc->setWmcid($this->getValue("./@id", $root));
        $wmc->setVersion($this->getValue("./@version", $root));
        $genEl = $this->getValue("./cntxt:General", $root);
        $stateHandler->setWindow(
            new Size(
                intval($this->getValue("./cntxt:Window/@width", $genEl)),
                intval($this->getValue("./cntxt:Window/@height", $genEl))
            )
        );
        $ext = $this->getBoundingBox(array("./cntxt:BoundingBox"), $genEl, null);
        if ($ext !== null) {
            $stateHandler->setExtent($ext);
            unset($ext);
        }

        $stateHandler->setName($this->getValue("./cntxt:Title/text()", $genEl));
        $keywordList = $this->xpath->query("./cntxt:KeywordList/cntxt:Keyword", $genEl);
        if ($keywordList !== null && $keywordList->length > 0) {
            $keywords = array();
            foreach ($keywordList as $keywordElt) {
                $keywords[] = $this->getValue("./text()", $keywordElt);
            }
            $wmc->setKeywords($keywords);
        }
        if ($this->getValue("./cntxt:Abstract", $genEl) !== null) {
            $wmc->setAbstract($this->getValue("./cntxt:Abstract/text()", $genEl));
        }
        $logoEl = $this->getValue("./cntxt:LogoURL", $genEl);
        if ($logoEl !== null) {
            $wmc->setLogourl(
                new LegendUrl(
                    new OnlineResource(
                        $this->getValue("./@format", $logoEl),
                        $this->getValue("./cntxt:OnlineResource/@xlink:href", $logoEl)
                    ),
                    intval($this->getValue("./@width", $logoEl)),
                    intval($this->getValue("./@height", $logoEl))
                )
            );
        }
        $descrEl = $this->getValue("./cntxt:DescriptionURL", $genEl);
        if ($descrEl !== null) {
            $wmc->setDescriptionurl(new OnlineResource(
                $this->getValue("./@format)", $descrEl),
                $this->getValue("./cntxt:OnlineResource/@xlink:href", $descrEl)
            ));
        }
        $contactEl = $this->getValue("./cntxt:ContactInformation", $genEl);
        if ($contactEl !== null) {
            $contact = new Contact();
            $contact->setPerson($this->getValue("./cntxt:ContactPersonPrimary/cntxt:ContactPerson/text()", $contactEl));
            $contact->setOrganization($this
                ->getValue("./cntxt:ContactPersonPrimary/cntxt:ContactOrganization/text()", $contactEl));
            $contact->setPosition($this->getValue("../cntxt:ContactPosition/text()", $contactEl));

            $addrEl = $this->getValue("./cntxt:ContactAddress", $contactEl);
            if ($addrEl !== null) {
                $contact->setAddressType($this->getValue("./cntxt:AddressType/text()", $addrEl));
                $contact->setAddress($this->getValue("./cntxt:Address/text()", $addrEl));
                $contact->setAddressCity($this->getValue("./cntxt:City/text()", $addrEl));
                $contact->setAddressStateOrProvince($this->getValue("./cntxt:StateOrProvince/text()", $addrEl));
                $contact->setAddressPostCode($this->getValue("./cntxt:PostCode/text()", $addrEl));
                $contact->setAddressCountry($this->getValue("./cntxt:Country/text()", $addrEl));
            }

            $contact->setVoiceTelephone($this->getValue("./cntxt:ContactVoiceTelephone/text()", $contactEl));
            $contact->setFacsimileTelephone($this->getValue("./cntxt:ContactFacsimileTelephone/text()", $contactEl));
            $contact->setElectronicMailAddress($this
                ->getValue("./cntxt:ContactElectronicMailAddress/text()", $contactEl));

            $wmc->setContact($contact);
        }
        $extensionEl = $this->getValue("./cntxt:Extension", $genEl);
        if ($extensionEl !== null) {
            $ext = $this->getBoundingBox(
                array("./mb3:maxExtent", "./mb:maxExtent", "./ol:maxExtent"),
                $extensionEl,
                $stateHandler->getExtent()->srs
            );
            if ($ext !== null) {
                $stateHandler->setMaxextent($ext);
                unset($ext);
            } else {
                $stateHandler->setMaxextent($stateHandler->getExtent());
            }
            //@ TODO other elements
        }
        $layerList = $this->xpath->query("./cntxt:LayerList/cntxt:Layer", $root);
        $sourcesTemp = array();
        foreach ($layerList as $layerElm) {
            $sourcesTemp[] = $this->parseLayer($layerElm, $stateHandler->getExtent()->srs, $infoFormat);
        }
        $groupSources = false;
        if ($groupSources) {
            foreach ($sourcesTemp as $sourcetmp) {
                $stateHandler->addSource($sourcetmp);
            }
        } else {
            foreach ($sourcesTemp as $sourcetmp) {
                $stateHandler->addSource($sourcetmp);
            }
        }
        $wmc->setState($stateHandler->generateState());
        return $wmc;
    }

    /**
     * Parses a layer form a WMC document LayerList
     *
     * @param \DOMElement $layerElm layer element
     * (xpath: '/ViewContext/LayerList/Layer')
     * @param string $srs wmc srs (srs from WMC document xpath:
     * '/ViewContext/General/BoundingBox/@SRS')
     * @return array layer configuration as array
     */
    private function parseLayer(\DOMElement $layerElm, $srs, $infoFormat)
    {
        $wmsinst = new WmsInstance();
        $wms = new WmsSource();
        $id = round(microtime(true) * 1000);
        $queryable = $this->getValue("./@queryable", $layerElm);
        $wmsinst->setVisible(!(bool) $this->getValue("./@hidden", $layerElm));
        $wmsinst->setInfoformat($infoFormat);
        $formats = array();
        $formatList = $this->xpath->query("./cntxt:FormatList/cntxt:Format", $layerElm);
        foreach ($formatList as $formatElm) {
            $formats[] = $this->getValue("./text()", $formatElm);
            $current = (bool) $this->getValue("./@current", $formatElm);
            if ($current) {
                $wmsinst->setFormat($this->getValue("./text()", $formatElm));
            }
        }
        $wms->setVersion($this->getValue("./cntxt:Server/@version", $layerElm));
        $getMap = new RequestInformation();
        $getMap->setHttpGet($this->getValue("./cntxt:Server/cntxt:OnlineResource/@xlink:href", $layerElm))
            ->setHttpPost(null)->setFormats($formats);
        $wms->setGetMap($getMap);
        $serverTitle = $this->getValue("./cntxt:Server/@xtitle", $layerElm);
        $serverTitle = $serverTitle === null ? $this->getValue("./cntxt:Title/text()", $layerElm) : $serverTitle;
        $wms->setTitle($serverTitle);
        $srsList = $this->xpath->query("./cntxt:SRS", $layerElm);
        $srses = array();
        foreach ($srsList as $srsElm) {
            $srses[] = $this->getValue("./text()", $srsElm);
        }

        $styleList = $this->xpath->query("./cntxt:StyleList/cntxt:Style", $layerElm);
        $styles = array();
        foreach ($styleList as $styleElm) {
            $current = (bool) $this->getValue("./@current", $styleElm);
            $style = new Style();
            $style->setName($this->getValue("./cntxt:Name/text()", $styleElm));
            $style->setTitle($this->getValue("./cntxt:Title/text()", $styleElm));
            $style->setAbstract($this->getValue("./cntxt:Abstract/text()", $styleElm));
            $legendUrlEl = $this->getValue("./cntxt:LegendURL", $styleElm);
            if ($legendUrlEl !== null) {
                $legendUrl = new LegendUrl();
                $legendUrl->setWidth($this->getValue("./@width", $legendUrlEl));
                $legendUrl->setHeight($this->getValue("./@height", $legendUrlEl));
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat($this->getValue("./format", $legendUrlEl));
                $onlineResource->setHref($this->getValue("./cntxt:OnlineResource/@xlink:href", $legendUrlEl));
                $legendUrl->setOnlineResource($onlineResource);
                $style->setLegendUrl($legendUrl);
            }
            //@ TODO cntxt:Style/cntxt:SLD
            $styles[] = $style;
        }


        $minScaleEl = $this->getValue("./sld:MinScaleDenominator", $layerElm);
        $maxScaleEl = $this->getValue("./sld:MaxScaleDenominator", $layerElm);
        $scale = null;
        if ($minScaleEl !== null || $maxScaleEl !== null) {
            $scale = new MinMax();
            $min = $this->getValue("./sld:MinScaleDenominator/text()", $layerElm);
            $scale->setMin($min !== null ? floatval($min) : null);
            $max = $this->getValue("./sld:MaxScaleDenominator/text()", $layerElm);
            $scale->setMax($max !== null ? floatval($max) : null);
        }
        $wmsinst->setId(intval($id))
            ->setTitle($wms->getTitle())
            ->setSource($wms);
        $wmsconf = new WmsInstanceConfiguration();
        $wmsconf->setType(strtolower($wmsinst->getType()));
        $wmsconf->setTitle($wmsinst->getTitle());
        $wmsconf->setIsBaseSource(false);
        $options = new WmsInstanceConfigurationOptions();
        $options->setUrl($wms->getGetMap()->getHttpGet())
            ->setVisible($wmsinst->getVisible())
            ->setFormat($wmsinst->getFormat())
            ->setVersion($wms->getVersion());

        $extensionEl = $this->getValue("./cntxt:Extension", $layerElm);
        $layerList = null;

        if ($extensionEl !== null) {
            $layerconfig = array();
            $layerconfig["maxExtent"] = $this
                ->getBoundingBox(array("./mb3:maxExtent"), $this->getValue("./cntxt:Extension", $extensionEl), $srs);
            $layerconfig["tiled"] = $this->findFirstValue(array("./mb3:tiled"), $extensionEl);
            $wmsinst
                ->setTransparency((bool) $this->findFirstValue(array("./mb3:transparent/text()"), $extensionEl, true))
                ->setOpacity($this->findFirstValue(array("./mb3:opacity"), $extensionEl, 1))
                ->setTiled((bool) $this->findFirstValue(array("./mb3:tiled"), $extensionEl, false));
            $layerList = $this->findFirstList(array("./mb3:layers/mb3:layer",
                "./*[contains(local-name(),'layers')]/*[contains(local-name(),'layer')]"), $extensionEl);

            $options->setTransparency($wmsinst->getTransparency())
                ->setOpacity($wmsinst->getOpacity())
                ->setTiled($wmsinst->getTiled())
                ->setInfoformat($wmsinst->getInfoformat());
        }
        $wmsconf->setOptions($options);

        $num = 0;
        $rootInst = new WmsInstanceLayer();
        $rootInst->setTitle($wmsinst->getTitle())
            ->setId($wmsinst->getId() . "_" . $num)
            ->setPriority($num)
            ->setSourceItem(new WmsLayerSource())
            ->setSourceInstance($wmsinst);
        $rootInst->setToggle(false);
        $rootInst->setAllowtoggle(true);
        $newLayerInstances = array();
        if ($layerList === null) {
            $layerListStr = explode(",", $this->getValue("./cntxt:Name/text()", $layerElm));

            foreach ($layerListStr as $layerStr) {
                $layerInst = new WmsInstanceLayer();
                $layersource = new WmsLayerSource();
                $layerInst->setTitle($layerStr);
                $layerInst->setSourceItem($layersource);
            }
        } else {
            foreach ($layerList as $layerElmMb) {
                $layerInst = new WmsInstanceLayer();
                $layersource = new WmsLayerSource();
                $layersource->setName($this->findFirstValue(array("./@name"), $layerElmMb, $num));
                $legendurl = $this->findFirstValue(array("./@legendUrl", "./@legend"), $layerElmMb, null);
                if ($legendurl !== null) {
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
                $queryable = $this->findFirstValue(array("./@queryable"), $layerElmMb, false);
                $queryable = $queryable !== null && strtolower($queryable) === 'true' ? true : null;
                $layerInst->setTitle($this->findFirstValue(array("./@title"), $layerElmMb, $num));
                $layerInst->setInfo($queryable);
                $layerInst->setSourceItem($layersource);
                $newLayerInstances[] = $layerInst;
            }
        }
        if ($newLayerInstances) {
            foreach ($newLayerInstances as $layerIndex => $newLayerInstance) {
                $newLayerInstance
                    ->setParent($rootInst)
                    ->setId($wmsinst->getId() . "_" . $num)
                    ->setPriority($num)
                    ->setSourceInstance($wmsinst);
                $rootInst->addSublayer($newLayerInstance);
                $wmsinst->addLayer($newLayerInstance);
            }
            $rootLayHandler = new WmsInstanceLayerEntityHandler($this->container, $rootInst);
            $children = array($rootLayHandler->generateConfiguration());
            $wmsconf->setChildren($children);
            return array(
                'type' => strtolower($wmsinst->getType()),
                'title' => $wmsinst->getTitle(),
                'id' => $wmsinst->getId(),
                'configuration' => $wmsconf->toArray());
        }
        return null;
    }

    /**
     * Returns the BoundingBox
     *
     * @param type $xpathStrArr
     * @param type $contextElm
     * @param type $defSrs
     * @return \Mapbender\CoreBundle\Component\BoundingBox|null
     */
    private function getBoundingBox($xpathStrArr, $contextElm, $defSrs)
    {
        if ($contextElm !== null) {
            $extentEl = $this->findFirstValue($xpathStrArr, $contextElm);
            if ($extentEl !== null) {
                if ($this->getValue("./@SRS", $extentEl) !== null) {
                    $srs = $this->getValue("./@SRS", $extentEl);
                } elseif ($this->getValue("./@srs", $extentEl) !== null) {
                    $srs = $this->getValue("./@srs", $extentEl);
                } else {
                    $srs = $defSrs;
                }
                return new BoundingBox(
                    $srs,
                    floatval($this->getValue("./@minx", $extentEl)),
                    floatval($this->getValue("./@miny", $extentEl)),
                    floatval($this->getValue("./@maxx", $extentEl)),
                    floatval($this->getValue("./@maxy", $extentEl))
                );
            }
            return null;
        }
        return null;
    }

    /**
     * Returns the first found value with xpath from $xpathStrArr.
     *
     * @param type $xpathStrArr array with xpathes
     * @param type $contextElm context element
     * @param type $defaultValue default value
     * @return string|\DOMElement|$defaultValue
     */
    private function findFirstValue($xpathStrArr, $contextElm, $defaultValue = null)
    {
        if ($contextElm !== null) {
            foreach ($xpathStrArr as $xpathStr) {
                $extentEl = $this->getValue($xpathStr, $contextElm);
                if ($extentEl !== null) {
                    return $extentEl;
                }
            }
            if ($defaultValue !== null) {
                return $defaultValue;
            }
            return null;
        }
        return null;
    }

    /**
     * Returns the first found DOMNodeList with xpath from $xpathStrArr.
     *
     * @param type $xpathStrArr array with xpathes
     * @param type $contextElm context element
     * @return \DOMNodeList
     */
    private function findFirstList($xpathStrArr, $contextElm)
    {
        if ($contextElm !== null) {
            foreach ($xpathStrArr as $xpathStr) {
                $extentList = $this->xpath->query($xpathStr, $contextElm);
                if ($extentList !== null && $extentList->length > 0) {
                    return $extentList;
                }
            }
            return null;
        }
        return null;
    }
}
