<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\WmtsBundle\Entity\TileMatrixSet;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Mapbender\WmtsBundle\Entity\WmtsSource;
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
     * @return WmtsSource
     */
    public function parse()
    {
        $vers = '1.0.0';
        $wmts = new WmtsSource();
        $wmts->setType(WmtsSource::TYPE_TMS);
        $root       = $this->doc->documentElement;
        $this->parseService($wmts, $root);
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
            $tilemap->parseTileMap($wmts, $doc->documentElement, $url_raw, $url_layer);
        }
        return $wmts;
    }
    
    /**
     * Parses the Service section of the GetCapabilities document
     *
     * @param WmtsSource $wmts
     * @param \DOMElement $cntxt
     */
    private function parseService(WmtsSource $wmts, \DOMElement $cntxt)
    {
        $wmts->setVersion($this->getValue("./@version"));
        $wmts->setTitle($this->getValue("./Title/text()"));
        $wmts->setDescription($this->getValue("./Abstract/text()"));

        $keywords = explode(' ', $this->getValue("./KeywordList/text()"));
        foreach ($keywords as $value) {
            $value = trim($value);
            if ($value) {
                $keyword = new WmtsSourceKeyword();
                $keyword->setValue($value);
                $keyword->setReferenceObject($wmts);
                $wmts->addKeyword($keyword);
            }
        }

        $contact = new Contact();
        $contact->setPerson(
            $this->getValue("./ContactInformation/ContactPersonPrimary/ContactPerson/text()", $cntxt)
        );
        $contact->setOrganization(
            $this->getValue("./ContactInformation/ContactPersonPrimary/ContactOrganization/text()", $cntxt)
        );
        $contact->setPosition(
            $this->getValue("./ContactInformation/ContactPosition/text()", $cntxt)
        );
        $contact->setAddressType(
            $this->getValue("./ContactInformation/ContactAddress/AddressType/text()", $cntxt)
        );
        $contact->setAddress(
            $this->getValue("./ContactInformation/ContactAddress/Address/text()", $cntxt)
        );
        $contact->setAddressCity(
            $this->getValue("./ContactInformation/ContactAddress/City/text()", $cntxt)
        );
        $contact->setAddressStateOrProvince(
            $this->getValue("./ContactInformation/ContactAddress/StateOrProvince/text()", $cntxt)
        );
        $contact->setAddressPostCode(
            $this->getValue("./ContactInformation/ContactAddress/PostCode/text()", $cntxt)
        );
        $contact->setAddressCountry(
            $this->getValue("./ContactInformation/ContactAddress/Country/text()", $cntxt)
        );
        $contact->setVoiceTelephone(
            $this->getValue("./ContactInformation/ContactVoiceTelephone/text()", $cntxt)
        );
        $contact->setFacsimileTelephone(
            $this->getValue("./ContactInformation/ContactFacsimileTelephone/text()", $cntxt)
        );
        $contact->setElectronicMailAddress(
            $this->getValue("./ContactInformation/ContactElectronicMailAddress/text()", $cntxt)
        );
        $wmts->setContact($contact);
    }
    
    public function parseTileMap(WmtsSource $wmts, \DOMElement $cntx, $url, $layerIdent)
    {
        #http://geo.sv.rostock.de/geodienste/luftbild/tms/1.0.0/luftbild/EPSG25833/5/10/10.png
        $layer = new WmtsLayerSource();
        $wmts->addLayer($layer);
        $layer->setSource($wmts);
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
        $wmts->addTilematrixset($tileSetsSet);
        $tileSetsSet->setSource($wmts);
    }
}
