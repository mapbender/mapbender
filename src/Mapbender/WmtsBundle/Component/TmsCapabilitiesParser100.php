<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\WmtsBundle\Component\Exception\WmtsException;
use Mapbender\WmtsBundle\Entity\TileMatrixSet;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Mapbender\WmtsBundle\Entity\WmtsSource;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;

/**
 * Class that Parses WMTS GetCapabilies Document
 * Parses WMTS GetCapabilities documents
 *
 * @author Paul Schmidt
 */
class TmsCapabilitiesParser100
{
    /**
     * The XML representation of the Capabilites Document
     * @var DOMDocument
     */
    protected $doc;

    /**
     * An Xpath-instance
     */
    protected $xpath;

    /**
     *
     * @var type
     */
    protected $proxy_config;

    /**
     *
     * @param array $proxy_config
     * @param \DOMDocument $doc
     */
    public function __construct($proxy_config, \DOMDocument $doc)
    {
        $this->proxy_config = $proxy_config;
        $this->doc   = $doc;
        $this->xpath = new \DOMXPath($doc);
    }

    public function getDoc()
    {
        return $this->doc;
    }

    /**
     * Finds the value
     * @param string $xpath xpath expression
     * @param \DOMNode $contextElm the node to use as context for evaluating the
     * XPath expression.
     * @return string the value of item or the selected item or null
     */
    protected function getValue($xpath, $contextElm = null)
    {
        if (!$contextElm) {
            $contextElm = $this->doc;
        }
        try {
            $elm = $this->xpath->query($xpath, $contextElm)->item(0);
            if (!$elm) {
                return null;
            }
            if ($elm->nodeType == XML_ATTRIBUTE_NODE) {
                return $elm->value;
            } elseif ($elm->nodeType == XML_TEXT_NODE) {
                return $elm->wholeText;
            } elseif ($elm->nodeType == XML_ELEMENT_NODE) {
                return $elm;
            } else {
                return null;
            }
        } catch (\Exception $E) {
            return null;
        }
    }

    /**
     * Creates a document
     *
     * @param string $data the string containing the XML
     * @param boolean $validate to validate of xml
     * @return \DOMDocument a GetCapabilites document
     * @throws XmlParseException if a GetCapabilities xml is not valid
     * @throws WmtsException if an service exception
     * @throws NotSupportedVersionException if a service version is not supported
     */
    public static function createDocument($data, $validate = false)
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
     * @param \DOMDocument $doc the GetCapabilities document
     * @return static
     * @throws NotSupportedVersionException if a service version is not supported
     */
    public static function getParser($proxy_config, \DOMDocument $doc)
    {
        $version = $doc->documentElement->getAttribute("version");
        switch ($version) {
            case "1.0.0":
                return new TmsCapabilitiesParser100($proxy_config, $doc);
            default:
                throw new NotSupportedVersionException('mb.wmts.repository.parser.not_supported_version');
        }
    }

    /**
     * Parses the GetCapabilities document
     * @return \Mapbender\WmtsBundle\Entity\WmtsSource
     */
    public function parse()
    {
        $vers = '1.0.0';
        $wmts = new WmtsSource(WmtsSource::TYPE_TMS);
        $root       = $this->doc->documentElement;
        $this->parseService($wmts, $root);
        $titleMapElts = $this->xpath->query("./TileMaps/TileMap", $root);
        
        foreach ($titleMapElts as $titleMapElt) {
//            $this->parseTileMap($wmtssource, $titleMapElt);
//            $title       = $this->getValue("./@title", $titleMapElt);
//            $srs         = $this->getValue("./@srs", $titleMapElt);
//            $profile     = $this->getValue("./@profile", $titleMapElt);
            $url         = $this->getValue("./@href", $titleMapElt);
            $proxy_query = ProxyQuery::createFromUrl($url);
            $proxy       = new CommonProxy($this->proxy_config, $proxy_query);
            try {
                $browserResponse = $proxy->handle();
                $content         = $browserResponse->getContent();
                $doc             = new \DOMDocument();
                if (!@$doc->loadXML($content)) {
                    throw new XmlParseException("mb.wmts.repository.parser.couldnotparse");
                }
                $tilemap = new TmsCapabilitiesParser100($this->proxy_config, $doc);
                $root    = $tilemap->getDoc()->documentElement;
                // Url Service endpoint (without the version number)
                $pos_vers = strpos($url, $vers);
                $url_raw = $pos_vers ? substr($url, 0, $pos_vers) : $url;
                $url_layer = substr($url, $pos_vers + strlen($vers) + 1);
                $tilemap->parseTileMap($wmts, $root, $url_raw, $url_layer);
            } catch (\Exception $e) {
                $this->removeFiles();
                throw $e;
            }
        }
        return $wmts;
    }
    
    /**
     * Parses the Service section of the GetCapabilities document
     *
     * @param \Mapbender\WmsBundle\Entity\WmsSource $wms the WmsSource
     * @param \DOMElement $cntxt the element to use as context for
     * the Service section
     */
    private function parseService(WmtsSource $wmts, \DOMElement $cntxt)
    {
        $wmts->setVersion($this->getValue("./@version", $cntxt));
        $wmts->setTitle($this->getValue("./Title/text()", $cntxt));
        $wmts->setDescription($this->getValue("./Abstract/text()", $cntxt));

        $keywordsStr = $this->getValue("./KeywordList/text()", $cntxt);
        if ($keywordsStr && $keywordList = explode(' ', $keywordsStr)) {
            $keywords      = new ArrayCollection();
            foreach ($keywordList as $keyword) {
                $keyword = new WmtsSourceKeyword();
                $keyword->setValue(trim($keyword));
                $keyword->setReferenceObject($wmts);
                $keywords->add($keyword);
            }
            $wmts->setKeywords($keywords);
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
    
    public function parseTileMap(WmtsSource &$wmts, \DOMElement $cntx, $url, $layerIdent)
    {
//        $title   = $this->getValue("./@title", $titleMapElt);
//        $srs     = $this->getValue("./@srs", $titleMapElt);
//        $profile = $this->getValue("./@profile", $titleMapElt);
//        $url     = $this->getValue("./@href", $titleMapElt);
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

        /* TODO
         * /TileMap/Metadata
         * /TileMap/Attribution
            | <Attribution>
            |   <Title>Goverment of British Columbia</Title>
            |   <Logo width="10" height="10" href="http://www.gov.bc.ca/logo.gif" mime-type="image/gif" />
            | </Attribution>
         * /TileMap/WebMapContext
            | <WebMapContext href="http://openmaps.gov.bc.ca" />
         * /TileMap/Face
            | <Face>0</Face>
         */

//        $ident = $this->getValue("./TileSets/@profile", $cntx) . $srs
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
