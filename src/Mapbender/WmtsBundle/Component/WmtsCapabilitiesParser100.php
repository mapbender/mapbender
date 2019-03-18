<?php

namespace Mapbender\WmtsBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\WmtsBundle\Entity\LegendUrl;
use Mapbender\WmtsBundle\Entity\Theme;
use Mapbender\WmtsBundle\Entity\TileMatrixSet;
use Mapbender\WmtsBundle\Entity\WmtsSource;
use Mapbender\WmtsBundle\Entity\WmtsSourceKeyword;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;

/**
 * Class that Parses WMTS 1.1.0 GetCapabilies Document
 * @package Mapbender
 * @author Paul Schmidt
 */
class WmtsCapabilitiesParser100 extends WmtsCapabilitiesParser
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
            $nsUri    = $node->nodeValue;
            if ($nsPrefix == "" && $nsUri == "http://www.opengis.net/wmts/1.0") {
                $nsPrefix = "wmts";
            }
            $this->xpath->registerNamespace($nsPrefix, $nsUri);
        }
    }

    /**
     * Parses the GetCapabilities document
     * @return \Mapbender\WmtsBundle\Entity\WmtsSource
     */
    public function parse()
    {
        $wmtssource = new WmtsSource(WmtsSource::TYPE_WMTS);
        $root       = $this->doc->documentElement;

        $wmtssource->setVersion($this->getValue("./@version", $root));
        $serviceIdentEl = $this->getValue("./ows:ServiceIdentification", $root);
        if ($serviceIdentEl) {
            $this->parseServiceIdentification($wmtssource, $serviceIdentEl);
        }
        $serverProviderEl = $this->getValue("./ows:ServiceProvider", $root);
        if ($serverProviderEl) {
            $this->parseServiceProvider($wmtssource, $serverProviderEl);
        }
        $operationsMetadata = $this->getValue("./ows:OperationsMetadata", $root);
        if ($operationsMetadata) {
            $this->parseCapabilityRequest($wmtssource, $this->getValue("./ows:OperationsMetadata", $root));
        }

        $serviceMetadataUrl = $this->getValue("./wmts:ServiceMetadataURL/@xlink:href", $root);
        $wmtssource->setServiceMetadataURL($serviceMetadataUrl);

        $layerElms = $this->xpath->query("./wmts:Contents/wmts:Layer", $root);
        foreach ($layerElms as $layerEl) {
            $layer = new WmtsLayerSource();
            $layer->setSource($wmtssource);
            $wmtssource->addLayer($layer);
            $this->parseLayer($layer, $layerEl);
        }
        $matrixsetElms = $this->xpath->query("./wmts:Contents/wmts:TileMatrixSet", $root);
        foreach ($matrixsetElms as $matrixsetElm) {
            $matrixset = new TileMatrixSet();
            $matrixset->setSource($wmtssource);
            $wmtssource->addTilematrixset($matrixset);
            $this->parseTilematrixset($matrixset, $matrixsetElm);
        }
        $themesElms = $this->xpath->query("./wmts:Themes/wmts:Theme", $root);
        foreach ($themesElms as $themeElm) {
            $theme = new Theme();
            $theme->setSource($wmtssource);
            $wmtssource->addTheme($theme);
            $this->parseTheme($theme, $themeElm);
        }
        return $wmtssource;
    }

    /**
     * Parses the ServiceIdentification section of the GetCapabilities document
     * @param \Mapbender\WmtsBundle\Entity\WmtsSource $wmts the WmtsSource
     * @param \DOMElement $contextElm the element to use as context for the ServiceIdentification section
     */
    private function parseServiceIdentification(WmtsSource $wmts, \DOMElement $contextElm)
    {
        $wmts->setTitle($this->getValue("./ows:Title/text()", $contextElm));
        $wmts->setDescription($this->getValue("./ows:Abstract/text()", $contextElm));

        $keywordElList = $this->xpath->query("./ows:KeywordList/ows:Keyword", $contextElm);
        $keywords      = new ArrayCollection();
        foreach ($keywordElList as $keywordEl) {
            $keyword = new WmtsSourceKeyword();
            $keyword->setValue(trim($this->getValue("./text()", $keywordEl)));
            $keyword->setReferenceObject($wmts);
            $keywords->add($keyword);
        }
        $wmts->setServiceType($this->getValue("./ows:ServiceType/text()", $contextElm));
        $wmts->setFees($this->getValue("./ows:Fees/text()", $contextElm));
        $wmts->setAccessConstraints($this->getValue("./ows:AccessConstraints/text()", $contextElm));
    }

    /**
     * Parses the ServiceProvider section of the GetCapabilities document
     * @param \Mapbender\WmtsBundle\Entity\WmtsSource $wmts the WmtsSource
     * @param \DOMElement $contextElm the element to use as context for the ServiceProvider section.
     */
    private function parseServiceProvider(WmtsSource $wmts, \DOMElement $contextElm)
    {
        $wmts->setServiceProviderSite($this->getValue("./wmts:OnlineResource/@xlink:href", $contextElm));
        $contact = new Contact();
        $contact->setOrganization($this->getValue("./ows:ProviderName/text()", $contextElm));
        $contact->setPerson($this->getValue("./ows:ServiceContact/ows:IndividualName/text()", $contextElm));
        $contact->setPosition($this->getValue("./ows:ServiceContact/ows:PositionName/text()", $contextElm));
        $contact->setVoiceTelephone(
            $this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Phone/ows:Voice/text()", $contextElm)
        );
        $contact->setFacsimileTelephone(
            $this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Phone/ows:Facsimile/text()", $contextElm)
        );
        $contact->setAddress(
            $this->getValue("./wmts:ContactInformation/wmts:ContactAddress/wmts:Address/text()", $contextElm)
        );
        $contact->setAddressCity(
            $this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Address/ows:DeliveryPoint/text()", $contextElm)
        );
        $contact->setAddressStateOrProvince(
            $this->getValue(
                "./ows:ServiceContact/ows:ContactInfo/ows:Address/ows:AdministrativeArea/text()",
                $contextElm
            )
        );
        $contact->setAddressPostCode(
            $this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Address/ows:PostalCode/text()", $contextElm)
        );
        $contact->setAddressCountry(
            $this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Address/ows:Country/text()", $contextElm)
        );
        $contact->setElectronicMailAddress(
            $this->getValue(
                "./ows:ServiceContact/ows:ContactInfo/ows:Address/ows:ElectronicMailAddress/text()",
                $contextElm
            )
        );
        $wmts->setContact($contact);
    }

    /**
     * Parses the Capabilities Request section of the GetCapabilities document
     * @param \Mapbender\WmtsBundle\Entity\WmtsSource $wmts the WmtsSource
     * @param \DOMElement $contextElm the element to use as context for the
     * Capabilities Request section
     */
    private function parseCapabilityRequest(WmtsSource $wmts, \DOMElement $contextElm)
    {
        $operations = $this->xpath->query("./*", $contextElm);
        foreach ($operations as $operation) {
            $name = $this->getValue("./@name", $operation);
            if ($name === "GetCapabilities") {
                $getCapabilities = $this->parseOperationRequestInformation($operation);
                $wmts->setGetCapabilities($getCapabilities);
            } elseif ($name === "GetTile") {
                $getTile = $this->parseOperationRequestInformation($operation);
                $wmts->setGetTile($getTile);
            } elseif ($name === "GetFeatureInfo") {
                $getFeatureInfo = $this->parseOperationRequestInformation($operation);
                $wmts->setGetFeatureInfo($getFeatureInfo);
            }
        }
    }

    /**
     * Parses the Operation Request Information section of the GetCapabilities
     * document.
     * @param \DOMElement $contextElm the element to use as context for the
     * Operation Request Information section
     * @return RequestInformation
     */
    private function parseOperationRequestInformation(\DOMElement $contextElm)
    {
        $ri       = new RequestInformation();
        $tempList = $this->xpath->query("./wmts:Format", $contextElm);
        if ($tempList !== null) {
            foreach ($tempList as $item) {
                $ri->addFormat($this->getValue("./text()", $item));
            }
        }
        $ri->setHttpGetRestful(
            $this->getValue(
                "./ows:DCP/ows:HTTP/ows:Get[./ows:Constraint/ows:AllowedValues/ows:Value/text()='RESTful']/@xlink:href",
                $contextElm
            )
        );
        $ri->setHttpGetKvp(
            $this->getValue(
                "./ows:DCP/ows:HTTP/ows:Get[./ows:Constraint/ows:AllowedValues/ows:Value/text()='KVP']/@xlink:href",
                $contextElm
            )
        );
        // check if only simple href is defined
        if (!$ri->getHttpGetRestful() && !$ri->getHttpGetKvp()) {
            $ri->setHttpGetKvp($this->getValue("./ows:DCP/ows:HTTP/ows:Get/@xlink:href", $contextElm));
        }
        return $ri;
    }

    /**
     * Parses a WMTS Layer
     * @param WmtsLayerSource $wmtslayer
     * @param \DOMElement $contextElm
     */
    private function parseLayer(WmtsLayerSource $wmtslayer, \DOMElement $contextElm)
    {
        $wmtslayer->setTitle($this->getValue("./ows:Title/text()", $contextElm));
        $wmtslayer->setAbstract($this->getValue("./ows:Abstract/text()", $contextElm));

        $latlonbboxEl = $this->getValue("./ows:WGS84BoundingBox", $contextElm);
        if ($latlonbboxEl !== null) {
            $latlonBounds = new BoundingBox();
            $bounds       = explode(
                " ",
                $this->getValue("./ows:LowerCorner/text()", $latlonbboxEl)
                . " " . $this->getValue("./ows:UpperCorner/text()", $latlonbboxEl)
            );
            $latlonBounds->setSrs("EPSG:4326");
            $latlonBounds
                ->setMinx($bounds[0])
                ->setMiny($bounds[1])
                ->setMaxx($bounds[2])
                ->setMaxy($bounds[3]);
            $wmtslayer->setLatlonBounds($latlonBounds);
        }

        $bboxEls = $this->xpath->query("./ows:BoundingBox", $contextElm);
        foreach ($bboxEls as $bboxEl) {
            $bbox = new BoundingBox();
            $bounds       = explode(
                " ",
                $this->getValue("./ows:LowerCorner/text()", $bboxEl)
                . " " . $this->getValue("./ows:UpperCorner/text()", $bboxEl)
            );
            $bbox->setSrs($this->getValue("./@crs", $bboxEl))
                ->setMinx($bounds[0])
                ->setMiny($bounds[1])
                ->setMaxx($bounds[2])
                ->setMaxy($bounds[3]);
            $wmtslayer->addBoundingBox($bbox);
        }

        $wmtslayer->setIdentifier($this->getValue("./ows:Identifier/text()", $contextElm));

        $stylesEl = $this->xpath->query("./wmts:Style", $contextElm);
        foreach ($stylesEl as $styleEl) {
            $style     = new Style();
            $style
                ->setTitle($this->getValue("./ows:Title/text()", $styleEl))
                ->setAbstract($this->getValue("./ows:Abstract/text()", $styleEl))
                ->setIdentifier($this->getValue("./ows:Identifier/text()", $styleEl))
                ->setIsdefault($this->getValue("./@isDefault", $styleEl));
            $legendurl = new LegendUrl();
            $legendurl->setFormat($this->getValue("./wmts:LegendURL/@format", $styleEl))
                ->setHref($this->getValue("./wmts:LegendURL/@xlink:href", $styleEl));
            if ($legendurl->getHref()) {
                $style->setLegendurl($legendurl);
            }
            $wmtslayer->addStyle($style);
        }

        $formatsFiEls = $this->xpath->query("./wmts:InfoFormat", $contextElm);
        foreach ($formatsFiEls as $formatEl) {
            $wmtslayer->addInfoformat($this->getValue("./text()", $formatEl));
        }
        $dimsEls = $this->xpath->query("./wmts:Dimension", $contextElm);
        foreach ($dimsEls as $dimEl) {
            $dim        = new \Mapbender\WmtsBundle\Entity\Dimension();
            $dim->setCurrent($this->getValue("./wmts:Current/text()", $dimEl))
                ->setDefault($this->getValue("./wmts:Default/text()", $dimEl))
                ->setIdentifier($this->getValue("./ows:Identifier/text()", $dimEl))
                ->setOum($this->getValue("./ows:UOM/text()", $dimEl))
                ->setUnitSymbol($this->getValue("./wmts:UnitSymbol/text()", $dimEl));
            $valuesElms = $this->xpath->query("./wmts:Value", $dimEl);
            foreach ($valuesElms as $valueElm) {
                $dim->addValue($this->getValue("./text()", $valueElm));
            }
            $wmtslayer->addDimension($dim);
        }

        $tmslsEls = $this->xpath->query("./wmts:TileMatrixSetLink", $contextElm);
        foreach ($tmslsEls as $tmslEl) {
            $tmsl = new TileMatrixSetLink();
            $tmsl->setTileMatrixSet($this->getValue("./wmts:TileMatrixSet/text()", $tmslEl))
                ->setTileMatrixSetLimits($this->getValue("./wmts:TileMatrixSetLimits/text()", $tmslEl));
            $wmtslayer->addTilematrixSetlinks($tmsl);
        }

        $resourceUrlElms = $this->xpath->query("./wmts:ResourceURL", $contextElm);
        foreach ($resourceUrlElms as $resourceUrlElm) {
            $resourceUrl = new UrlTemplateType();
            $wmtslayer->addResourceUrl(
                $resourceUrl
                    ->setFormat($this->getValue("./@format", $resourceUrlElm))
                    ->setResourceType($this->getValue("./@resourceType", $resourceUrlElm))
                    ->setTemplate($this->getValue("./@template", $resourceUrlElm))
            );
        }
    }

    /**
     * Parses a TileMatrixSet
     * @param TileMatrixSet $tilematrixset
     * @param \DOMElement $contextElm
     */
    private function parseTilematrixset(TileMatrixSet $tilematrixset, \DOMElement $contextElm)
    {
        $tilematrixset->setIdentifier($this->getValue("./ows:Identifier/text()", $contextElm));
        $tilematrixset->setTitle($this->getValue("./ows:Title/text()", $contextElm));
        $tilematrixset->setAbstract($this->getValue("./ows:Abstract/text()", $contextElm));
        $tilematrixset->setSupportedCrs($this->getValue("./ows:SupportedCRS/text()", $contextElm));
        $tileMatrixEls = $this->xpath->query("./wmts:TileMatrix", $contextElm);
        foreach ($tileMatrixEls as $tileMatrixEl) {
            $tileMatrix = new TileMatrix();
            $tileMatrix->setIdentifier($this->getValue("./ows:Identifier/text()", $tileMatrixEl));
            $tileMatrix
                ->setScaledenominator(floatval($this->getValue("./wmts:ScaleDenominator/text()", $tileMatrixEl)));
            $topleft = array_map(
                create_function('$value', 'return (float) $value;'),
                explode(' ', $this->getValue("./wmts:TopLeftCorner/text()", $tileMatrixEl))
            );
            $tileMatrix->setTopleftcorner($topleft);
            $tileMatrix->setMatrixwidth(intval($this->getValue("./wmts:MatrixWidth/text()", $tileMatrixEl)));
            $tileMatrix->setMatrixheight(intval($this->getValue("./wmts:MatrixHeight/text()", $tileMatrixEl)));
            $tileMatrix->setTilewidth(intval($this->getValue("./wmts:TileWidth/text()", $tileMatrixEl)));
            $tileMatrix->setTileheight(intval($this->getValue("./wmts:TileHeight/text()", $tileMatrixEl)));
            $tilematrixset->addTilematrix($tileMatrix);
        }
    }

    /**
     * Parses a Theme
     * @param Theme $theme
     * @param \DOMElement $contextElm
     */
    private function parseTheme(Theme $theme, \DOMElement $contextElm)
    {
        $theme->setIdentifier($this->getValue("./ows:Identifier/text()", $contextElm));
        $theme->setTitle($this->getValue("./ows:Title/text()", $contextElm));
        $theme->setAbstract($this->getValue("./ows:Abstract/text()", $contextElm));
//        $tilematrixset->setSupportedCrs($this->getValue("./ows:SupportedCRS/text()", $contextElm));
        $layerRefsEls = $this->xpath->query("./wmts:LayerRef", $contextElm);
        foreach ($layerRefsEls as $layerRefEl) {
            $theme->addLayerRef($this->getValue("./text()", $layerRefEl));
        }
        $themeEls = $this->xpath->query("./wmts:Theme", $contextElm);
        foreach ($themeEls as $themeEl) {
            $theme_ = new Theme();
//            $theme_->setSource($theme->getSource());
            $theme->addTheme($theme_);
            $theme_->setParent($theme);
            $this->parseTheme($theme_, $themeEl);
        }
    }
}
