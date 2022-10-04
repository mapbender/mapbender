<?php


namespace Mapbender\WmtsBundle\Component\Wmts;


use Mapbender\Component\SourceLoader;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\XmlValidatorService;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmtsBundle\Component\Exception\NoWmtsDocument;
use Mapbender\WmtsBundle\Component\TmsCapabilitiesParser100;
use Mapbender\WmtsBundle\Component\WmtsCapabilitiesParser;

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
     * @inheritdoc
     * @throws \Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException
     * @throws \Mapbender\CoreBundle\Component\Exception\XmlParseException
     * @throws \Mapbender\WmtsBundle\Component\Exception\WmtsException
     */
    public function parseResponseContent($content)
    {
        try {
            $document = WmtsCapabilitiesParser::createDocument($content);
            $source = WmtsCapabilitiesParser::getParser($document)->parse();
        } catch (NoWmtsDocument $e) {
            $document = TmsCapabilitiesParser100::createDocument($content);
            $source = TmsCapabilitiesParser100::getParser($this->httpTransport, $document)->parse();
        }
        return $source;
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
        try {
            $document = WmtsCapabilitiesParser::createDocument($content);
        } catch (NoWmtsDocument $e) {
            $document = TmsCapabilitiesParser100::createDocument($content);
        }
        $this->validator->validateDocument($document);
    }
}
