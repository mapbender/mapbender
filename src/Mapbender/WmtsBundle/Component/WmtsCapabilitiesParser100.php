<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace Mapbender\WmtsBundle\Component;

use Mapbender\Component\CapabilitiesDomParser;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\WmtsBundle\Entity\HttpTileSource;
use Mapbender\WmtsBundle\Entity\LegendUrl;
use Mapbender\WmtsBundle\Entity\Theme;
use Mapbender\WmtsBundle\Entity\TileMatrixSet;
use Mapbender\WmtsBundle\Entity\WmtsSource;
use Mapbender\WmtsBundle\Entity\WmtsSourceKeyword;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;

/**
 * @author Paul Schmidt
 */
class WmtsCapabilitiesParser100 extends CapabilitiesDomParser
{
    /**
     * @return WmtsSource
     */
    public function parse(\DOMDocument $doc)
    {
        $version = $doc->documentElement->getAttribute("version");
        if ('1.0.0' !== $version) {
            // @todo: Show the user the incompatible version number
            throw new NotSupportedVersionException('mb.wms.repository.parser.not_supported_version');
        }

        $source = HttpTileSource::wmtsFactory();
        /** @var \DOMElement $root */
        $root = $doc->documentElement;

        $source->setVersion($root->getAttribute('version'));
        $serviceIdentEl = $this->getFirstChildNode($root, 'ServiceIdentification');
        if ($serviceIdentEl) {
            $this->parseServiceIdentification($source, $serviceIdentEl);
        }
        $serverProviderEl = $this->getFirstChildNode($root, 'ServiceProvider');
        if ($serverProviderEl) {
            $this->parseServiceProvider($source, $serverProviderEl);
        }
        $operationsMetadata = $this->getFirstChildNode($root, 'OperationsMetadata');
        if ($operationsMetadata) {
            $this->parseOperationsMetadata($source, $operationsMetadata);
        }

        foreach ($this->getChildNodesByTagName($root, 'Contents') as $contentsEl) {
            foreach ($this->getChildNodesByTagName($contentsEl, 'Layer') as $layerEl) {
                $layer = $this->parseLayer($layerEl);
                $source->addLayer($layer);
            }
            foreach ($this->getChildNodesByTagName($contentsEl, 'TileMatrixSet') as $matrixsetEl) {
                $matrixset = $this->parseTilematrixset($matrixsetEl);
                $matrixset->setSource($source);
                $source->addTilematrixset($matrixset);
            }
        }
        foreach ($this->getChildNodesByTagName($root, 'Themes') as $themesEl) {
            foreach ($this->getChildNodesByTagName($themesEl, 'Theme') as $themeEl) {
                $theme = $this->parseTheme($themeEl);
                $theme->setSource($source);
                $source->addTheme($theme);
            }
        }
        return $source;
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
        $keywordElements = $keywordWrap ? $this->getChildNodesByTagName($keywordWrap, 'Keyword') : array();
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
        $providerName = $this->getFirstChildNodeText($contextElm, 'ProviderName');
        $source->setServiceProviderName($providerName);
        $contact->setOrganization($providerName);
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
     * @param WmtsSource $source
     * @param \DOMElement $element
     */
    protected function parseOperationsMetadata(WmtsSource $source, \DOMElement $element)
    {
        foreach ($this->getChildNodesByTagName($element, 'Operation') as $operation) {
            switch ($operation->getAttribute('name')) {
                default:
                    // Do nothing
                    break;
                case 'GetTile':
                    $source->setGetTile($this->parseOperationRequestInformation($operation));
                    break;
                case 'GetFeatureInfo':
                    $source->setGetFeatureInfo($this->parseOperationRequestInformation($operation));
                    break;
            }
        }
    }

    /**
     * Parses the Operation Request Information section of the GetCapabilities
     * document.
     * @param \DOMElement $element
     * @return RequestInformation|null
     */
    private function parseOperationRequestInformation(\DOMElement $element)
    {
        $dcp = $this->getFirstChildNode($element, 'DCP');
        $http = $dcp ? $this->getFirstChildNode($dcp, 'HTTP') : null;
        $httpGetEls = $http ? $this->getChildNodesByTagName($http, 'Get') : null;

        $getRestful = null;
        $getKvp = null;

        foreach ($httpGetEls as $httpGetEl) {
            /** @var \DOMElement $httpGetEl */
            $allowedEncodings = $this->parseAllowedEncodings($httpGetEl);
            if (!$getRestful && \in_array('RESTful', $allowedEncodings)) {
                $getRestful = $httpGetEl->getAttribute('xlink:href');
            }
            if (!$getKvp && \in_array('KVP', $allowedEncodings)) {
                $getKvp = $httpGetEl->getAttribute('xlink:href');
            }
        }
        if (!$getRestful && !$getKvp && $httpGetEls) {
            // Uh-oh!
            $getKvp = $httpGetEls[0]->getAttribute('xlink:href');
        }
        if ($getRestful || $getKvp) {
            $ri = new RequestInformation();
            $ri->setHttpGetRestful($getRestful);
            $ri->setHttpGetKvp($getKvp);
            return $ri;
        } else {
            return null;
        }
    }

    protected function parseAllowedEncodings(\DOMElement $element)
    {
        $values = array();
        foreach ($this->getChildNodesByTagName($element, 'Constraint') as $constraintEl) {
            if ($constraintEl->getAttribute('name') === 'GetEncoding') {
                $allowedValuesEl = $this->getFirstChildNode($constraintEl, 'AllowedValues');
                $allowedValueEls = $allowedValuesEl ? $this->getChildNodesByTagName($allowedValuesEl, 'Value') : array();
                foreach ($allowedValueEls as $allowedValueEl) {
                    $values[] = $allowedValueEl->textContent;
                }
                break;
            }
        }
        return $values;
    }

    /**
     * @param \DOMElement $element
     * @return WmtsLayerSource
     */
    private function parseLayer(\DOMElement $element)
    {
        $layer = new WmtsLayerSource();

        $layer->setTitle($this->getFirstChildNodeText($element, 'Title'));
        $layer->setAbstract($this->getFirstChildNodeText($element, 'Abstract'));
        $layer->setIdentifier($this->getFirstChildNodeText($element, 'Identifier'));

        foreach ($this->getChildNodesByTagName($element, 'WGS84BoundingBox') as $bboxElm) {
            $bbox = $this->parseBoundingBox($bboxElm);
            $bbox->setSrs('EPSG:4326');
            $layer->setLatlonBounds($bbox);
            break;
        }
        foreach ($this->getChildNodesByTagName($element, 'BoundingBox') as $bboxElm) {
            $layer->addBoundingBox($this->parseBoundingBox($bboxElm));
        }

        foreach ($this->getChildNodesByTagName($element, 'Style') as $styleEl) {
            $layer->addStyle($this->parseStyle($styleEl));
        }
        foreach ($this->getChildNodesByTagName($element, 'InfoFormat') as $infoFormatEl) {
            $layer->addInfoformat($infoFormatEl->textContent);
        }

        foreach ($this->getChildNodesByTagName($element, 'TileMatrixSetLink') as $tilematrixsetEl) {
            $layer->addTilematrixSetlinks($this->parseTileMatrixSetLink($tilematrixsetEl));
        }

        foreach ($this->getChildNodesByTagName($element, 'ResourceURL') as $resourceUrlEl) {
            $layer->addResourceUrl($this->parseLayerResourceUrl($resourceUrlEl));
        }
        return $layer;
    }

    /**
     * @param \DOMElement $element
     * @return Style
     */
    protected function parseStyle(\DOMElement $element)
    {
        $style = new Style();
        $style
            ->setTitle($this->getFirstChildNodeText($element, 'Title'))
            ->setAbstract($this->getFirstChildNodeText($element, 'Abstract'))
            ->setIdentifier($this->getFirstChildNodeText($element, 'Identifier'))
            ->setIsDefault($element->getAttribute('isDefault') === 'true')
        ;
        $legendUrlEl = $this->getFirstChildNode($element, 'LegendURL');
        $legendHref = $legendUrlEl ? $legendUrlEl->getAttribute('xlink:href') : null;
        if ($legendHref) {
            $legendUrl = new LegendUrl();
            $legendUrl->setHref($legendHref);
            $legendUrl->setFormat($legendUrlEl->getAttribute('format'));
            $style->setLegendurl($legendUrl);
        }
        return $style;
    }

    /**
     * @param \DOMElement $element
     * @return TileMatrixSetLink
     */
    protected function parseTileMatrixSetLink(\DOMElement $element)
    {
        $link = new TileMatrixSetLink();
        $link
            ->setTileMatrixSet($this->getFirstChildNodeText($element, 'TileMatrixSet'))
            ->setTileMatrixSetLimits($this->getFirstChildNodeText($element, 'TileMatrixSetLimits'))
        ;
        return $link;
    }

    /**
     * @param \DOMElement $element
     * @return UrlTemplateType
     */
    protected function parseLayerResourceUrl(\DOMElement $element)
    {
        $resourceUrl = new UrlTemplateType();
        $resourceUrl
            ->setFormat($element->getAttribute('format'))
            ->setResourceType($element->getAttribute('resourceType'))
            ->setTemplate($element->getAttribute('template'))
        ;
        return $resourceUrl;
    }

    /**
     * @param \DOMElement $element
     * @return TileMatrixSet
     */
    private function parseTilematrixset(\DOMElement $element)
    {
        $tilematrixset = new TileMatrixSet();
        $tilematrixset->setIdentifier($this->getFirstChildNodeText($element, 'Identifier'));
        $tilematrixset->setTitle($this->getFirstChildNodeText($element, 'Title'));
        $tilematrixset->setAbstract($this->getFirstChildNodeText($element, 'Abstract'));
        $tilematrixset->setSupportedCrs($this->getFirstChildNodeText($element, 'SupportedCRS'));
        foreach ($this->getChildNodesByTagName($element, 'TileMatrix') as $tileMatrixEl) {
            $tileMatrix = new TileMatrix();
            $tileMatrix->setIdentifier($this->getFirstChildNodeText($tileMatrixEl, 'Identifier'));
            $tileMatrix->setScaledenominator(floatval($this->getFirstChildNodeText($tileMatrixEl, 'ScaleDenominator')));
            $topleft = array_map('\floatval', explode(' ', $this->getFirstChildNodeText($tileMatrixEl, 'TopLeftCorner')));
            $tileMatrix->setTopleftcorner($topleft);
            $tileMatrix->setMatrixwidth(intval($this->getFirstChildNodeText($tileMatrixEl, 'MatrixWidth')));
            $tileMatrix->setMatrixheight(intval($this->getFirstChildNodeText($tileMatrixEl, 'MatrixHeight')));
            $tileMatrix->setTilewidth(intval($this->getFirstChildNodeText($tileMatrixEl, 'TileWidth')));
            $tileMatrix->setTileheight(intval($this->getFirstChildNodeText($tileMatrixEl, 'TileHeight')));
            $tilematrixset->addTilematrix($tileMatrix);
        }
        return $tilematrixset;
    }

    /**
     * @param \DOMElement $element
     * @return Theme
     */
    private function parseTheme(\DOMElement $element)
    {
        $theme = new Theme();
        $theme->setIdentifier($this->getFirstChildNodeText($element, 'Identifier'));
        $theme->setTitle($this->getFirstChildNodeText($element, 'Title'));
        $theme->setAbstract($this->getFirstChildNodeText($element, 'Abstract'));
        foreach ($this->getChildNodesByTagName($element, 'LayerRef') as $layerRefEl) {
            $theme->addLayerRef($layerRefEl->textContent);
        }
        foreach ($this->getChildNodesByTagName($element, 'Theme') as $themeEl) {
            $childTheme = $this->parseTheme($themeEl);
            $theme->addTheme($childTheme);
            $childTheme->setParent($theme);
        }
        return $theme;
    }

    protected function parseBoundingBox(\DOMElement $element)
    {
        $crs = $element->getAttribute('crs') ?: null;
        $lowerCorner = \explode(' ', $this->getFirstChildNodeText($element, 'LowerCorner'));
        $upperCorner = \explode(' ', $this->getFirstChildNodeText($element, 'UpperCorner'));
        return new BoundingBox($crs, $lowerCorner[0], $lowerCorner[1], $upperCorner[0], $upperCorner[1]);
    }
}
