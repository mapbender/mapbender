<?php


namespace Mapbender\WmtsBundle\Component\Wmts;


use Mapbender\Component\SourceLoader;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\XmlValidatorService;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\Exception\Loader\ServerResponseErrorException;
use Mapbender\WmtsBundle\Component\TmsCapabilitiesParser100;
use Mapbender\WmtsBundle\Component\WmtsCapabilitiesParser100;
use Mapbender\WmtsBundle\Entity\HttpTileSource;

class Loader extends SourceLoader
{
    /** @var XmlValidatorService */
    protected $validator;

    /**
     * @param HttpTransportInterface $httpTransport
     * @param XmlValidatorService $validator
     */
    public function __construct(HttpTransportInterface $httpTransport, XmlValidatorService $validator)
    {
        parent::__construct($httpTransport);
        $this->validator = $validator;
    }

    public function getTypeCode()
    {
        // HACK: do not show separate Wmts + Tms type choices
        //       when loading a new source
        return strtolower(Source::TYPE_WMTS);
    }

    public function getTypeLabel()
    {
        // HACK: do not show separate Wmts + Tms type choices
        //       when loading a new source
        return 'OGC WMTS / TMS';
    }

    /**
     * @throws \Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException
     * @throws XmlParseException
     * @throws ServerResponseErrorException
     * @return HttpTileSource
     */
    public function parseResponseContent($content)
    {
        $doc = $this->xmlToDom($content);
        switch ($doc->documentElement->tagName) {
            // @todo: DI, handlers, prechecks
            default:
                // @todo: use a different exception to indicate lack of support
                throw new XmlParseException('mb.wms.repository.parser.not_supported_document');
            case 'TileMapService':
                $parser = new TmsCapabilitiesParser100($this->httpTransport);
                return $parser->parse($doc);
            case 'Capabilities':
                $parser = new WmtsCapabilitiesParser100();
                return $parser->parse($doc);
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidUrlException
     */
    protected function getResponse(HttpOriginInterface $origin)
    {
        $url = $origin->getOriginUrl();
        static::validateUrl($url);
        $url = UrlUtil::addCredentials($url, $origin->getUsername(), $origin->getPassword());
        return $this->httpTransport->getUrl($url);
    }

    public function validateResponseContent($content)
    {
        $this->validator->validateDocument($this->xmlToDom($content));
    }
}
