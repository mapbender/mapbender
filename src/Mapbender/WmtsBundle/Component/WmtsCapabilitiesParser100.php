<?php /** @noinspection PhpComposerExtensionStubsInspection */

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
     * @return WmtsSource
     */
    public function parse()
    {
        $wmtssource = new WmtsSource();
        $root       = $this->doc->documentElement;

        $wmtssource->setVersion($this->getValue("./@version"));
        $serviceIdentEl = $this->getFirstChildNode($root, 'ServiceIdentification');
        if ($serviceIdentEl) {
            $this->parseServiceIdentification($wmtssource, $serviceIdentEl);
        }
        $serverProviderEl = $this->getFirstChildNode($root, 'ServiceProvider');
        if ($serverProviderEl) {
            $this->parseServiceProvider($wmtssource, $serverProviderEl);
        }
        $operationsMetadata = $this->getValue("./ows:OperationsMetadata");
        if ($operationsMetadata) {
            $this->parseCapabilityRequest($wmtssource, $this->getValue("./ows:OperationsMetadata"));
        }

        $layerElms = $this->xpath->query("./wmts:Contents/wmts:Layer");
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
     * @param WmtsSource $source
     * @param \DOMElement $contextElm
     */
    private function parseServiceIdentification(WmtsSource $source, \DOMElement $contextElm)
    {
        $source->setTitle($this->getFirstChildNodeText($contextElm, 'Title'));
        $source->setDescription($this->getFirstChildNodeText($contextElm, 'Abstract'));

        $keywordWrap = $this->getFirstChildNode($contextElm, 'Keywords');
        $keywordElements = $keywordWrap ? $keywordWrap->getElementsByTagName('Keyword') : array();
        foreach ($keywordElements as $keywordElement) {
            $text = \trim($keywordElement->textContent);
            if ($text) {
                $keyword = new WmtsSourceKeyword();
                $keyword->setValue($text);
                $keyword->setReferenceObject($source);
                $source->addKeyword($keyword);
            }
        }
        $source->setFees($this->getFirstChildNodeText($contextElm, 'Fees'));
        $source->setAccessConstraints($this->getFirstChildNodeText($contextElm, 'AccessConstraints'));
    }

    /**
     * Parses the ServiceProvider section of the GetCapabilities document
     * @param WmtsSource $source
     * @param \DOMElement $contextElm
     */
    private function parseServiceProvider(WmtsSource $source, \DOMElement $contextElm)
    {
        $contact = new Contact();
        $contact->setOrganization($this->getFirstChildNodeText($contextElm, 'ProviderName'));
        $serviceContactEl = $this->getFirstChildNode($contextElm, 'ServiceContact');
        $providerSiteEl = $this->getFirstChildNode($contextElm, 'ProviderSite');
        if ($providerSiteEl) {
            $source->setServiceProviderSite($providerSiteEl->getAttribute('xlink:href'));
        }
        $contactInfoEl = $serviceContactEl ? $this->getFirstChildNode($serviceContactEl, 'ContactInfo') : null;
        $addressEl = $contactInfoEl ? $this->getFirstChildNode($contactInfoEl, 'Address') : null;
        $phoneEl = $contactInfoEl ? $this->getFirstChildNode($contactInfoEl, 'Phone') : null;

        if ($serviceContactEl) {
            $contact->setPerson($this->getFirstChildNodeText($serviceContactEl, 'IndividualName'));
            $contact->setPosition($this->getFirstChildNodeText($serviceContactEl, 'PositionName'));
        }
        if ($phoneEl) {
            $contact->setVoiceTelephone($this->getFirstChildNodeText($phoneEl, 'Voice'));
            $contact->setFacsimileTelephone($this->getFirstChildNodeText($phoneEl, 'Facsimile'));
        }
        if ($addressEl) {
            $contact->setAddressCity($this->getFirstChildNodeText($addressEl, 'City'));
            $contact->setAddressStateOrProvince($this->getFirstChildNodeText($addressEl, 'AdministrativeArea'));
            $contact->setAddressPostCode($this->getFirstChildNodeText($addressEl, 'PostalCode'));
            $contact->setAddressCountry($this->getFirstChildNodeText($addressEl, 'Country'));
            $contact->setElectronicMailAddress($this->getFirstChildNodeText($addressEl, 'ElectronicMailAddress'));
        }
        $source->setContact($contact);
    }

    /**
     * Parses the Capabilities Request section of the GetCapabilities document
     * @param WmtsSource $wmts
     * @param \DOMElement $contextElm
     */
    private function parseCapabilityRequest(WmtsSource $wmts, \DOMElement $contextElm)
    {
        $operations = $this->xpath->query("./*", $contextElm);
        foreach ($operations as $operation) {
            $name = $this->getValue("./@name", $operation);
            if ($name === "GetTile") {
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
     * @param \DOMElement $contextElm
     * @return RequestInformation
     */
    private function parseOperationRequestInformation(\DOMElement $contextElm)
    {
        $ri       = new RequestInformation();
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

        foreach ($contextElm->getElementsByTagName('WGS84BoundingBox') as $bboxElm) {
            $bbox = $this->parseBoundingBox($bboxElm);
            $bbox->setSrs('EPSG:4326');
            $wmtslayer->setLatlonBounds($bbox);
            break;
        }
        foreach ($contextElm->getElementsByTagName('BoundingBox') as $bboxElm) {
            $wmtslayer->addBoundingBox($this->parseBoundingBox($bboxElm));
        }

        $wmtslayer->setIdentifier($this->getValue("./ows:Identifier/text()", $contextElm));

        $stylesEl = $this->xpath->query("./wmts:Style", $contextElm);
        foreach ($stylesEl as $styleEl) {
            $style     = new Style();
            $style
                ->setTitle($this->getValue("./ows:Title/text()", $styleEl))
                ->setAbstract($this->getValue("./ows:Abstract/text()", $styleEl))
                ->setIdentifier($this->getValue("./ows:Identifier/text()", $styleEl))
                ->setIsDefault($this->getValue("./@isDefault", $styleEl));
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
        foreach ($contextElm->getElementsByTagName('TileMatrix') as $tileMatrixEl) {
            $tileMatrix = new TileMatrix();
            $tileMatrix->setIdentifier($this->getValue("./ows:Identifier/text()", $tileMatrixEl));
            $tileMatrix
                ->setScaledenominator(floatval($this->getValue("./wmts:ScaleDenominator/text()", $tileMatrixEl)));
            $topleft = array_map('\floatval', explode(' ', $this->getValue("./wmts:TopLeftCorner/text()", $tileMatrixEl)));
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

    protected function parseBoundingBox(\DOMElement $element)
    {
        $crs = $element->getAttribute('crs') ?: null;
        $lowerCorner = \explode(' ', $element->getElementsByTagName('LowerCorner')->item(0)->textContent);
        $upperCorner = \explode(' ', $element->getElementsByTagName('UpperCorner')->item(0)->textContent);
        return new BoundingBox($crs, $lowerCorner[0], $lowerCorner[1], $upperCorner[0], $upperCorner[1]);
    }
}
