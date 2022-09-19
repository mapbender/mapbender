<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace Mapbender\WmsBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\Component\CapabilitiesDomParser;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\WmsBundle\Component\Exception\WmsException;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsLayerSourceKeyword;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\WmsBundle\Entity\WmsSourceKeyword;

/**
 * Parses WMS GetCapabilities documents
 *
 * @author Karim Malhas
 * @author Paul Schmidt
 */
abstract class WmsCapabilitiesParser extends CapabilitiesDomParser
{

    /**
     * The XML representation of the Capabilites Document
     * @var \DOMDocument
     */
    protected $doc;

    /**
     * The resolution
     *
     * @var integer
     */
    protected $resolution = 72;

    /**
     * Creates an instance
     *
     * @param \DOMDocument $doc
     */
    public function __construct(\DOMDocument $doc)
    {
        $this->doc = $doc;
    }

    /**
     * Parses the GetCapabilities document
     *
     * @return WmsSource
     */
    public function parse()
    {
        $wms = new WmsSource();
        $root = $this->doc->documentElement;
        $wms->setVersion($root->getAttribute('version'));
        $this->parseService($wms, $this->getFirstChildNode($root, 'Service'));
        $this->parseCapabilityList($wms, $this->getFirstChildNode($root, 'Capability'));
        return $wms;
    }

    protected function parseService(WmsSource $source, \DOMElement $serviceEl)
    {
        $source->setName($this->getFirstChildNodeText($serviceEl, 'Name'));
        $source->setTitle($this->getFirstChildNodeText($serviceEl, 'Title'));
        $source->setDescription($this->getFirstChildNodeText($serviceEl, 'Abstract'));
        $source->setOnlineResource($this->getFirstOnlineResourceHref($serviceEl));
        $source->setFees($this->getFirstChildNodeText($serviceEl, 'Fees'));
        $source->setAccessConstraints($this->getFirstChildNodeText($serviceEl, 'AccessConstraints'));

        $contactInfomationEl = $this->getFirstChildNode($serviceEl, 'ContactInformation');
        if ($contactInfomationEl) {
            $source->setContact($this->parseContactInformation($contactInfomationEl));
        } else {
            $source->setContact(new Contact());
        }
        $keywords = $this->parseKeywordList($this->getFirstChildNode($serviceEl, 'KeywordList'));
        $keywordCollection = new ArrayCollection();
        foreach ($keywords as $keywordText) {
            $keyword = new WmsSourceKeyword();
            $keyword->setValue($keywordText);
            $keyword->setReferenceObject($source);
            $keywordCollection->add($keyword);
        }
        $source->setKeywords($keywordCollection);
    }

    protected function parseCapabilityList(WmsSource $source, \DOMElement $capabilityEl)
    {
        foreach ($capabilityEl->childNodes as $childEl) {
            if ($childEl->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            switch ($childEl->localName) {
                default:
                    // Do nothing
                    break;
                case 'Request':
                    $this->parseCapabilityRequest($source, $childEl);
                    break;
                case 'Exception':
                    $this->parseExceptionFormats($source, $childEl);
                    break;
                case 'Layer':
                    $rootlayer = $this->parseLayer($source, $childEl);
                    $rootlayer->setSource($source);
                    $source->addLayer($rootlayer);
                    break;
                case 'UserDefinedSymbolization':
                    $this->parseUserDefinedSymbolization($source, $childEl);
                    break;
            }
        }
        return $source;
    }

    /**
     * @param WmsSource $source
     * @param \DOMElement $contextElm
     */
    protected function parseCapabilityRequest(WmsSource $source, \DOMElement $contextElm)
    {
        foreach ($contextElm->childNodes as $operationEl) {
            if ($operationEl->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $operationRequestInfo = $this->parseOperationRequestInformation($operationEl);
            switch ($operationEl->localName) {
                default:
                    // Do nothing
                    break;
                case 'GetCapabilities':
                    $source->setGetCapabilities($operationRequestInfo);
                    break;
                case 'GetMap':
                    $source->setGetMap($operationRequestInfo);
                    break;
                case 'GetFeatureInfo':
                    $source->setGetFeatureInfo($operationRequestInfo);
                    break;
                case 'GetLegendGraphic':
                    $source->setGetLegendGraphic($operationRequestInfo);
                    break;
                case 'DescribeLayer':
                    $source->setDescribeLayer($operationRequestInfo);
                    break;
                case 'GetStyles':
                    $source->setGetStyles($operationRequestInfo);
                    break;
                case 'PutStyles':
                    $source->setPutStyles($operationRequestInfo);
                    break;
            }
        }
    }

    /**
     * @param \DOMElement $operationEl
     * @return RequestInformation
     */
    protected function parseOperationRequestInformation(\DOMElement $operationEl)
    {
        $requestInformation = new RequestInformation();
        foreach ($this->getChildNodesByTagName($operationEl, 'Format') as $formatEl) {
            $requestInformation->addFormat($formatEl->textContent);
        }
        $dcpTypeEl = $this->getFirstChildNode($operationEl, 'DCPType');
        $httpEl = $dcpTypeEl ? $this->getFirstChildNode($dcpTypeEl, 'HTTP') : null;
        $httpGetEl = $httpEl ? $this->getFirstChildNode($httpEl, 'Get') : null;
        $httpPostEl = $httpEl ? $this->getFirstChildNode($httpEl, 'Post') : null;
        $requestInformation->setHttpGet($httpGetEl ? $this->getFirstOnlineResourceHref($httpGetEl) : null);
        $requestInformation->setHttpPost($httpPostEl ? $this->getFirstOnlineResourceHref($httpPostEl) : null);
        return $requestInformation;
    }

    /**
     * @param WmsSource $source
     * @param \DOMElement $capabilitiesEl
     */
    protected function parseExceptionFormats(WmsSource $source, \DOMElement $capabilitiesEl)
    {
        foreach ($this->getChildNodesByTagName($capabilitiesEl, 'Format') as $formatEl) {
            $source->addExceptionFormat($formatEl->textContent);
        }
    }

    /**
     * @param WmsSource $source
     * @param \DOMElement $symbolizationEl
     */
    protected function parseUserDefinedSymbolization(WmsSource $source, \DOMElement $symbolizationEl)
    {
        $source->setSupportSld($symbolizationEl->getAttribute('SupportSLD'));
        $source->setUserLayer($symbolizationEl->getAttribute('UserLayer'));
        $source->setUserStyle($symbolizationEl->getAttribute('UserStyle'));
        $source->setRemoteWfs($symbolizationEl->getAttribute('RemoteWFS'));
        $source->setInlineFeature($symbolizationEl->getAttribute('InlineFeature'));
        $source->setRemoteWcs($symbolizationEl->getAttribute('RemoteWCS'));
    }

    protected function parseContactInformation(\DOMElement $ciEl)
    {
        $personPrimaryEl = $this->getFirstChildNode($ciEl, 'ContactPersonPrimary');
        $addressEl = $this->getFirstChildNode($ciEl, 'ContactAddress');
        $contact = new Contact();
        if ($personPrimaryEl) {
            $contact->setPerson($this->getFirstChildNodeText($personPrimaryEl, 'ContactPerson'));
            $contact->setOrganization($this->getFirstChildNodeText($personPrimaryEl, 'ContactOrganization'));
        }
        $contact->setPosition($this->getFirstChildNodeText($ciEl, 'ContactPosition'));
        if ($addressEl) {
            $contact->setAddressType($this->getFirstChildNodeText($addressEl, 'AddressType'));
            $contact->setAddress($this->getFirstChildNodeText($addressEl, 'Address'));
            $contact->setAddressCity($this->getFirstChildNodeText($addressEl, 'City'));
            $contact->setAddressStateOrProvince($this->getFirstChildNodeText($addressEl, 'StateOrProvince'));
            $contact->setAddressPostCode($this->getFirstChildNodeText($addressEl, 'PostCode'));
            $contact->setAddressCountry($this->getFirstChildNodeText($addressEl, 'Country'));
        }
        $contact->setVoiceTelephone($this->getFirstChildNodeText($ciEl, 'ContactVoiceTelephone'));
        $contact->setFacsimileTelephone($this->getFirstChildNodeText($ciEl, 'ContactFacsimileTelephone'));
        $contact->setElectronicMailAddress($this->getFirstChildNodeText($ciEl, 'ContactElectronicMailAddress'));
        return $contact;
    }

    protected function parseLayer(WmsSource $source, \DOMElement $layerEl)
    {
        $layer = new WmsLayerSource();
        $layer->setQueryable($layerEl->getAttribute('queryable'));
        $layer->setCascaded($layerEl->getAttribute('cascaded'));
        $layer->setOpaque($layerEl->getAttribute('opaque'));
        $layer->setNoSubset($layerEl->getAttribute('noSubsets'));
        $layer->setFixedWidth($layerEl->getAttribute('fixedWidth'));
        $layer->setFixedHeight($layerEl->getAttribute('fixedHeight'));
        $layer->setName($this->getFirstChildNodeText($layerEl, 'Name'));
        $layer->setTitle($this->getFirstChildNodeText($layerEl, 'Title'));
        $layer->setAbstract($this->getFirstChildNodeText($layerEl, 'Abstract'));

        foreach ($this->getChildNodesByTagName($layerEl, 'Layer') as $childLayerEl) {
            $childLayer = $this->parseLayer($source, $childLayerEl);
            $childLayer->setSource($source);
            $layer->addSublayer($childLayer);
            $source->addLayer($childLayer);
        }

        foreach ($this->getChildNodesByTagName($layerEl, 'Style') as $styleEl) {
            $layer->addStyle($this->parseLayerStyle($styleEl));
        }

        $layer->setLatlonBounds($this->getLayerLatLonBounds($layerEl));

        foreach ($this->getChildNodesByTagName($layerEl, 'BoundingBox') as $bboxEl) {
            $layer->addBoundingBox($this->parseLayerBoundingBox($bboxEl));
        }

        foreach ($this->getLayerDimensions($layerEl) as $dimension) {
            if ($dimension->getName() && $dimension->getExtent()) {
                $layer->addDimension($dimension);
            }
        }

        $attributionEl = $this->getFirstChildNode($layerEl, 'Attribution');
        if ($attributionEl) {
            $layer->setAttribution($this->parseLayerAttribution($attributionEl));
        }
        foreach ($this->getChildNodesByTagName($layerEl, 'AuthorityURL') as $authorityEl) {
            $authority = new Authority();
            $authority->setName($authorityEl->getAttribute('name'));
            $authority->setUrl($this->getFirstOnlineResourceHref($authorityEl));
            $layer->addAuthority($authority);
        }
        foreach ($this->getChildNodesByTagName($layerEl, 'Identifier') as $identifierEl) {
            $identifier = new Identifier();
            $identifier->setAuthority($identifierEl->getAttribute('name'));
            $identifier->setValue($identifierEl->textContent);
            $layer->setIdentifier($identifier);
            break;
        }
        foreach ($this->getChildNodesByTagName($layerEl, 'DataURL') as $dataUrlEl) {
            $resource = new OnlineResource();
            $resource->setFormat($this->getFirstChildNodeText($dataUrlEl, 'Format'));
            $resource->setHref($this->getFirstOnlineResourceHref($dataUrlEl));
            $layer->addDataUrl($resource);
        }

        foreach ($this->getChildNodesByTagName($layerEl, 'MetadataURL') as $metaUrlEl) {
            $metadataUrl = new MetadataUrl();
            $metadataUrl->setType($metaUrlEl->getAttribute('type'));
            $resource = new OnlineResource();
            $resource->setFormat($this->getFirstChildNodeText($metaUrlEl, 'Format'));
            $resource->setHref($this->getFirstOnlineResourceHref($metaUrlEl));
            $metadataUrl->setOnlineResource($resource);
            $layer->addMetadataUrl($metadataUrl);
        }

        foreach ($this->getChildNodesByTagName($layerEl, 'FeatureListURL') as $featureListEl) {
            $resource = new OnlineResource();
            $resource->setFormat($this->getFirstChildNodeText($featureListEl, 'Format'));
            $resource->setHref($this->getFirstOnlineResourceHref($featureListEl));
            $layer->addFeatureListUrl($resource);
        }

        $keywords = $this->parseKeywordList($this->getFirstChildNode($layerEl, 'KeywordList'));
        $keywordCollection = new ArrayCollection();
        foreach ($keywords as $keywordText) {
            $keyword = new WmsLayerSourceKeyword();
            $keyword->setValue($keywordText);
            $keyword->setReferenceObject($layer);
            $keywordCollection->add($keyword);
        }
        $layer->setKeywords($keywordCollection);
        return $layer;
    }

    protected function parseLayerStyle(\DOMElement $styleEl)
    {
        $style = new Style();
        $style->setName($this->getFirstChildNodeText($styleEl, 'Name'));
        $style->setTitle($this->getFirstChildNodeText($styleEl, 'Title'));
        $style->setAbstract($this->getFirstChildNodeText($styleEl, 'Abstract'));
        foreach ($this->getChildNodesByTagName($styleEl, 'LegendURL') as $legendEl) {
            $legendUrl = new LegendUrl();
            $onlineResource = new OnlineResource();
            $legendUrl->setOnlineResource($onlineResource);
            $legendUrl->setWidth($legendEl->getAttribute('width'));
            $legendUrl->setHeight($legendEl->getAttribute('height'));
            $onlineResource->setFormat($this->getFirstChildNodeText($legendEl, 'Format'));
            $onlineResource->setHref($this->getFirstOnlineResourceHref($legendEl));
            $style->setLegendUrl($legendUrl);
            break;
        }
        return $style;
    }

    protected function parseLayerAttribution(\DOMElement $attributionEl)
    {
        $attribution = new Attribution();
        $attribution->setTitle($this->getFirstChildNodeText($attributionEl, 'Title'));
        $attribution->setOnlineResource($this->getFirstOnlineResourceHref($attributionEl));
        $logoUrl = new LegendUrl();
        foreach ($this->getChildNodesByTagName($attributionEl, 'LogoURL') as $logoEl) {
            $logoUrl->setWidth($logoEl->getAttribute('width'));
            $logoUrl->setHeight($logoEl->getAttribute('height'));
            $logoResource = new OnlineResource();
            $logoResource->setFormat($this->getFirstChildNodeText($logoEl, 'Format'));
            $logoResource->setHref($this->getFirstOnlineResourceHref($logoEl));
            $logoUrl->setOnlineResource($logoResource);
            break;
        }
        return $attribution;
    }

    protected function parseLayerBoundingBox(\DOMElement $element = null)
    {
        if ($element) {
            $bbox = new BoundingBox();
            $bbox->setMinx($element->getAttribute('minx'));
            $bbox->setMiny($element->getAttribute('miny'));
            $bbox->setMaxx($element->getAttribute('maxx'));
            $bbox->setMaxy($element->getAttribute('maxy'));
            return $bbox;
        } else {
            return null;
        }
    }

    abstract protected function getLayerLatLonBounds(\DOMElement $layerEl);

    /**
     * @param \DOMElement $layerEl
     * @return Dimension[]
     */
    protected function getLayerDimensions(\DOMElement $layerEl)
    {
        $dimensions = array();
        foreach ($this->getChildNodesByTagName($layerEl, 'Dimension') as $dimensionEl) {
            $name = $dimensionEl->getAttribute('name');
            if (!$name) {
                continue;
            }
            $dimension = new Dimension();
            $dimension->setName($name);
            $dimension->setUnits($dimensionEl->getAttribute('units'));
            $dimension->setUnitSymbol($dimensionEl->getAttribute('unitSymbol'));
            $dimension->setDefault($dimensionEl->getAttribute('default'));
            $dimension->setMultipleValues(!!$dimensionEl->getAttribute('multipleValues'));
            $dimension->setNearestValue(!!$dimensionEl->getAttribute('nearestValue'));
            $dimension->setCurrent(!!$dimensionEl->getAttribute('current'));
            $dimension->setExtent(\trim($dimensionEl->textContent));
            $dimensions[$name] = $dimension;
        }
        return $dimensions;
    }

    /**
     * @param \DOMElement|null $listEl
     * @return string[]
     */
    protected function parseKeywordList(\DOMElement $listEl = null)
    {
        $keywords = array();
        $children = $listEl ? $this->getChildNodesByTagName($listEl, 'Keyword') : array();
        foreach ($children as $keywordEl) {
            $text = \trim($keywordEl->textContent);
            if ($text) {
                $keywords[] = $text;
            }
        }
        return $keywords;
    }

    protected function getFirstOnlineResourceHref(\DOMElement $parent, $default=null)
    {
        foreach ($this->getChildNodesByTagName($parent, 'OnlineResource') as $onlineResourceEl) {
            return $onlineResourceEl->getAttribute('xlink:href');
        }
        return $default;
    }

    /**
     * Creates a document
     *
     * @param string $data the string containing the XML
     * @return \DOMDocument a GetCapabilites document
     * @throws XmlParseException if a GetCapabilities xml is not valid
     * @throws WmsException if an service exception
     * @throws NotSupportedVersionException if a service version is not supported
     */
    public static function createDocument($data)
    {
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($data)) {
            throw new XmlParseException("mb.wms.repository.parser.couldnotparse");
        }
        if ($doc->documentElement->tagName == "ServiceExceptionReport") {
            $message = $doc->documentElement->nodeValue;
            throw new WmsException($message);
        }

        if ($doc->documentElement->tagName !== "WMS_Capabilities"
            && $doc->documentElement->tagName !== "WMT_MS_Capabilities") {
            throw new NotSupportedVersionException("mb.wms.repository.parser.not_supported_document");
        }

        $version = $doc->documentElement->getAttribute("version");
        if ($version !== "1.1.1" && $version !== "1.3.0") {
            throw new NotSupportedVersionException('mb.wms.repository.parser.not_supported_version');
        }
        return $doc;
    }

    /**
     * Gets a capabilities parser
     *
     * @param \DOMDocument $doc the GetCapabilities document
     * @return WmsCapabilitiesParser111 | WmsCapabilitiesParser130 a capabilities parser
     * @throws NotSupportedVersionException if a service version is not supported
     */
    public static function getParser(\DOMDocument $doc)
    {
        $version = $doc->documentElement->getAttribute("version");
        switch ($version) {
            case "1.1.1":
                return new WmsCapabilitiesParser111($doc);
            case "1.3.0":
                return new WmsCapabilitiesParser130($doc);
            default:
                throw new NotSupportedVersionException('mb.wms.repository.parser.not_supported_version');
        }
    }
}
