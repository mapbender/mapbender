<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace Mapbender\WmtsBundle\Component;

# https://geo.sv.rostock.de/geodienste/luftbild_mv-20/tms/1.0.0


use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\WmtsBundle\Entity\HttpTileSource;
use Mapbender\WmtsBundle\Entity\TileMatrixSet;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Mapbender\WmtsBundle\Entity\WmtsSourceKeyword;

/**
 * @author Paul Schmidt
 */
class TmsCapabilitiesParser100 extends AbstractTileServiceParser
{
    /**
     * The XML representation of the Capabilites Document
     * @var \DOMDocument
     */
    protected $doc;

    /** @var HttpTransportInterface */
    protected $httpTransport;

    /**
     * @param HttpTransportInterface $httpTransport
     * @param \DOMDocument $doc
     */
    public function __construct(HttpTransportInterface $httpTransport, \DOMDocument $doc)
    {
        parent::__construct(new \DOMXPath($doc));
        $this->httpTransport = $httpTransport;
        $this->doc   = $doc;
    }

    /**
     * Creates a document
     *
     * @param string $data the string containing the XML
     * @return \DOMDocument a GetCapabilites document
     * @throws XmlParseException if a GetCapabilities xml is not valid
     * @throws NotSupportedVersionException if a service version is not supported
     */
    public static function createDocument($data)
    {
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($data)) {
            throw new XmlParseException("mb.wmts.repository.parser.couldnotparse");
        }

        $version = $doc->documentElement->getAttribute("version");
        if ($version !== "1.0.0") {
            throw new NotSupportedVersionException('mb.wmts.repository.parser.not_supported_version');
        }
        return $doc;
    }

    /**
     * Gets a capabilities parser
     *
     * @param HttpTransportInterface $httpTransport
     * @param \DOMDocument $doc the GetCapabilities document
     * @return static
     * @throws NotSupportedVersionException if a service version is not supported
     */
    public static function getParser(HttpTransportInterface $httpTransport, \DOMDocument $doc)
    {
        $version = $doc->documentElement->getAttribute("version");
        switch ($version) {
            case "1.0.0":
                return new TmsCapabilitiesParser100($httpTransport, $doc);
            default:
                throw new NotSupportedVersionException('mb.wmts.repository.parser.not_supported_version');
        }
    }

    /**
     * Parses the GetCapabilities document
     * @return HttpTileSource
     */
    public function parse()
    {
        $source = HttpTileSource::tmsFactory();
        $vers = '1.0.0';

        $root       = $this->doc->documentElement;
        $this->parseService($source, $root);
        $titleMapElts = $this->xpath->query("./TileMaps/TileMap", $root);
        
        foreach ($titleMapElts as $titleMapElt) {
            $url         = $this->getValue("./@href", $titleMapElt);
            $content = $this->httpTransport->getUrl($url)->getContent();
            $doc             = new \DOMDocument();
            if (!@$doc->loadXML($content)) {
                throw new XmlParseException("mb.wmts.repository.parser.couldnotparse");
            }
            $tilemap = new TmsCapabilitiesParser100($this->httpTransport, $doc);
            // Url Service endpoint (without the version number)
            $pos_vers = strpos($url, $vers);
            $url_raw = $pos_vers ? substr($url, 0, $pos_vers) : $url;
            $url_layer = substr($url, $pos_vers + strlen($vers) + 1);
            $tilemap->parseTileMap($source, $doc->documentElement, $url_raw, $url_layer);
        }
        return $source;
    }
    
    /**
     * Parses the Service section of the GetCapabilities document
     *
     * @param HttpTileSource $source
     * @param \DOMElement $cntxt
     */
    private function parseService(HttpTileSource $source, \DOMElement $cntxt)
    {
        $source->setVersion($this->getValue("./@version"));
        $source->setTitle($this->getValue("./Title/text()"));
        $source->setDescription($this->getValue("./Abstract/text()"));

        $keywords = explode(' ', $this->getValue("./KeywordList/text()"));
        foreach ($keywords as $value) {
            $value = trim($value);
            if ($value) {
                $keyword = new WmtsSourceKeyword();
                $keyword->setValue($value);
                $keyword->setReferenceObject($source);
                $source->addKeyword($keyword);
            }
        }
        $contact = new Contact();   // Default empty object if no info found
        foreach ($cntxt->getElementsByTagName('ContactInformation') as $contactInfoEl) {
            $contact = $this->parseContactInformation($contactInfoEl);
            break;
        }
        $source->setContact($contact);
    }

    protected function parseContactInformation(\DOMElement $element)
    {
        $personPrimaryEl = $this->getFirstChildNode($element, 'ContactPersonPrimary');
        $addressEl = $this->getFirstChildNode($element, 'ContactAddress');
        $contact = new Contact();
        if ($personPrimaryEl) {
            $contact->setPerson($this->getFirstChildNodeText($personPrimaryEl, 'ContactPerson'));
            $contact->setOrganization($this->getFirstChildNodeText($personPrimaryEl, 'ContactOrganization'));
        }
        $contact->setPosition($this->getFirstChildNodeText($element, 'ContactPosition'));
        if ($addressEl) {
            $contact->setAddressType($this->getFirstChildNodeText($addressEl, 'AddressType'));
            $contact->setAddress($this->getFirstChildNodeText($addressEl, 'Address'));
            $contact->setAddressCity($this->getFirstChildNodeText($addressEl, 'City'));
            $contact->setAddressStateOrProvince($this->getFirstChildNodeText($addressEl, 'StateOrProvince'));
            $contact->setAddressPostCode($this->getFirstChildNodeText($addressEl, 'PostCode'));
            $contact->setAddressCountry($this->getFirstChildNodeText($addressEl, 'Country'));
        }
        $contact->setVoiceTelephone($this->getFirstChildNodeText($element, 'ContactVoiceTelephone'));
        $contact->setFacsimileTelephone($this->getFirstChildNodeText($element, 'ContactFacsimileTelephone'));
        $contact->setElectronicMailAddress($this->getFirstChildNodeText($element, 'ContactElectronicMailAddress'));
        return $contact;
    }
    
    public function parseTileMap(HttpTileSource $source, \DOMElement $cntx, $url, $layerIdent)
    {
        $layer = new WmtsLayerSource();
        $source->addLayer($layer);
        $layer->setSource($source);
        $layer->setTitle($this->getValue("./Title/text()", $cntx));
        $layer->setAbstract($this->getValue("./Abstract/text()", $cntx));
        $layer->setIdentifier($layerIdent);

        $srs = $this->getValue("./SRS/text()", $cntx);
        $profile = $this->getValue("./TileSets/@profile", $cntx);
        $format = $this->getValue("./TileFormat/@mime-type", $cntx);
        $tilewidth = $this->getValue("./TileFormat/@width", $cntx);
        $tileheight = $this->getValue("./TileFormat/@height", $cntx);

        $resourceUrl = new UrlTemplateType();
        $layer->addResourceUrl(
            $resourceUrl
                ->setFormat($format)
                ->setResourceType(null)
                ->setTemplate($url)
        );

        $tmsl = new TileMatrixSetLink();
        $tmsl->setTileMatrixSet($layerIdent);
        $layer->addTilematrixSetlinks($tmsl);

        $bbox = new BoundingBox();
        $bbox->setSrs($srs)
            ->setMinx($this->getValue("./BoundingBox/@minx", $cntx))
            ->setMiny($this->getValue("./BoundingBox/@miny", $cntx))
            ->setMaxx($this->getValue("./BoundingBox/@maxx", $cntx))
            ->setMaxy($this->getValue("./BoundingBox/@maxy", $cntx));
        $layer->addBoundingBox($bbox);

        $origin = array(
            floatval($this->getValue("./Origin/@x", $cntx)),
            floatval($this->getValue("./Origin/@y", $cntx))
        );

        $tileSetsSet = new TileMatrixSet();
        $tileSetsSet->setIdentifier($layerIdent);
        $tileSetsSet->setTitle($this->getValue("./Title/text()", $cntx));
        $tileSetsSet->setAbstract($this->getValue("./Abstract/text()", $cntx));
        $tileSetsSet->setSupportedCrs($this->getValue("./SRS/text()", $cntx));
        $tileSetsElts = $this->xpath->query("./TileSets/TileSet", $cntx);

        foreach ($tileSetsElts as $tileSetEl) {
            $tileSet = new TileMatrix();
            $tileSet->setIdentifier($this->getValue("./@order", $tileSetEl));
            $tileSet->setScaledenominator(floatval($this->getValue("./@units-per-pixel", $tileSetEl)));
            $tileSet->setHref($this->getValue("./@href", $tileSetEl));
            $tileSet->setTopleftcorner($origin);
            $tileSet->setTilewidth($tilewidth);
            $tileSet->setTileheight($tileheight);
            $tileSetsSet->addTilematrix($tileSet);
        }
        $source->addTilematrixset($tileSetsSet);
        $tileSetsSet->setSource($source);
    }
}
