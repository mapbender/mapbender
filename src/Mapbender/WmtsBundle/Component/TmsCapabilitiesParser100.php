<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace Mapbender\WmtsBundle\Component;

# https://geo.sv.rostock.de/geodienste/luftbild_mv-20/tms/1.0.0


use Mapbender\Component\CapabilitiesDomParser;
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
class TmsCapabilitiesParser100 extends CapabilitiesDomParser
{

    /** @var HttpTransportInterface */
    protected $httpTransport;

    /**
     * @param HttpTransportInterface $httpTransport
     */
    public function __construct(HttpTransportInterface $httpTransport)
    {
        $this->httpTransport = $httpTransport;
    }

    /**
     * @return HttpTileSource
     */
    public function parse(\DOMDocument $doc)
    {
        $vers = $doc->documentElement->getAttribute("version");
        if ('1.0.0' !== $vers) {
            // @todo: Show the user the incompatible version number
            throw new NotSupportedVersionException('mb.wms.repository.parser.not_supported_version');
        }

        $source = HttpTileSource::tmsFactory();

        $root =$doc->documentElement;
        $this->parseService($source, $root);

        foreach ($this->getChildNodesFromNamePath($root, array('TileMaps', 'TileMap')) as $tileMapEl) {
            $url = $tileMapEl->getAttribute('href');
            $content = $this->httpTransport->getUrl($url)->getContent();
            $doc             = new \DOMDocument();
            if (!@$doc->loadXML($content)) {
                throw new XmlParseException('mb.wms.repository.parser.couldnotparse');
            }
            // Url Service endpoint (without the version number)
            $pos_vers = strpos($url, $vers);
            $url_raw = $pos_vers ? substr($url, 0, $pos_vers) : $url;
            $url_layer = substr($url, $pos_vers + strlen($vers) + 1);
            $this->parseTileMap($source, $doc->documentElement, $url_raw, $url_layer);
        }
        return $source;
    }
    
    /**
     * @param HttpTileSource $source
     * @param \DOMElement $rootNode
     */
    private function parseService(HttpTileSource $source, \DOMElement $rootNode)
    {
        $source->setVersion($rootNode->getAttribute('version'));
        $source->setTitle($this->getFirstChildNodeText($rootNode, 'Title'));
        $source->setDescription($this->getFirstChildNodeText($rootNode, 'Abstract'));

        $keywords = \array_filter(\preg_split('#\s+#u', $this->getFirstChildNodeText($rootNode, 'KeywordList')));
        foreach ($keywords as $value) {
            $keyword = new WmtsSourceKeyword();
            $keyword->setValue($value);
            $keyword->setReferenceObject($source);
            $source->addKeyword($keyword);
        }
        $contact = new Contact();   // Default empty object if no info found
        foreach ($rootNode->getElementsByTagName('ContactInformation') as $contactInfoEl) {
            $contact = $this->parseContactInformation($contactInfoEl);
            break;
        }
        $source->setContact($contact);
    }

    protected function parseTileMap(HttpTileSource $source, \DOMElement $cntx, $url, $layerIdent)
    {
        $layer = new WmtsLayerSource();
        $source->addLayer($layer);
        $layer->setTitle($this->getFirstChildNodeText($cntx, 'Title'));
        $layer->setAbstract($this->getFirstChildNodeText($cntx, 'Abstract'));
        $layer->setIdentifier($layerIdent);

        $srs = $this->getFirstChildNodeText($cntx, 'SRS');
        $tileFormatEl = $this->getFirstChildNode($cntx, 'TileFormat');

        $resourceUrl = new UrlTemplateType();
        $resourceUrl->setTemplate($url);
        $resourceUrl->setFormat($tileFormatEl->getAttribute('mime-type'));
        $resourceUrl->setExtension($tileFormatEl->getAttribute('extension'));
        $layer->addResourceUrl($resourceUrl);

        $tmsl = new TileMatrixSetLink();
        $tmsl->setTileMatrixSet($layerIdent);
        $layer->addTilematrixSetlinks($tmsl);

        $bboxEl = $this->getFirstChildNode($cntx, 'BoundingBox');
        $bbox = $this->parseBoundingBox($bboxEl);
        $bbox->setSrs($srs);
        $layer->addBoundingBox($bbox);
        $originEl = $this->getFirstChildNode($cntx, 'Origin');
        $origin = array(
            floatval($originEl->getAttribute('x')),
            floatval($originEl->getAttribute('y')),
        );

        $matrixSet = new TileMatrixSet();
        $matrixSet->setIdentifier($layerIdent);
        $matrixSet->setTitle($layer->getTitle());
        $matrixSet->setAbstract($layer->getAbstract());
        $matrixSet->setSupportedCrs($srs);
        foreach ($this->getChildNodesFromNamePath($cntx, array('TileSets', 'TileSet')) as $tileSetEl) {
            $tileMatrix = $this->parseTileSet($tileSetEl);
            $tileMatrix->setTopleftcorner($origin);
            $tileMatrix->setTilewidth($tileFormatEl->getAttribute('width'));
            $tileMatrix->setTileheight($tileFormatEl->getAttribute('height'));
            $matrixSet->addTilematrix($tileMatrix);
        }
        $source->addTilematrixset($matrixSet);
        $matrixSet->setSource($source);
    }

    protected function parseBoundingBox(\DOMElement $element)
    {
        $bbox = new BoundingBox();
        $bbox->setMinx($element->getAttribute('minx'));
        $bbox->setMiny($element->getAttribute('miny'));
        $bbox->setMaxx($element->getAttribute('maxx'));
        $bbox->setMaxy($element->getAttribute('maxy'));
        return $bbox;
    }

    protected function parseTileSet(\DOMElement $element)
    {
        $tileMatrix = new TileMatrix();
        $tileMatrix->setIdentifier($element->getAttribute('order'));
        $tileMatrix->setScaledenominator(\floatval($element->getAttribute('units-per-pixel')));
        $tileMatrix->setHref($element->getAttribute('href'));
        return $tileMatrix;
    }

    /**
     * Nested child element lookup convenience method.
     *
     * @param \DOMElement $parent
     * @param string[] $path
     * @return \DOMElement[]
     */
    protected static function getChildNodesFromNamePath(\DOMElement $parent, array $path)
    {
        $path = \array_values($path);
        if (count($path) > 1) {
            $matches = array();
            $remainingPath = \array_slice($path, 1);
            foreach (static::getChildNodesByTagName($parent, $path[0]) as $nextParent) {
                $matches = \array_merge($matches, static::getChildNodesFromNamePath($nextParent, $remainingPath));
            }
            return $matches;
        } else {
            return static::getChildNodesByTagName($parent, $path[0]);
        }
    }
}
