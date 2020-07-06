<?php


namespace Mapbender\WmtsBundle\Component\Wmts;


use Mapbender\Component\Loader\SourceLoaderResponse;
use Mapbender\Component\SourceLoader;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\XmlValidator;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmtsBundle\Component\Exception\NoWmtsDocument;
use Mapbender\WmtsBundle\Component\TmsCapabilitiesParser100;
use Mapbender\WmtsBundle\Component\WmtsCapabilitiesParser;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Loader extends SourceLoader
{
    /** @var mixed[] */
    protected $proxyConfig;
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param HttpTransportInterface $httpTransport
     * @param mixed[] $proxyConfig
     */
    public function __construct(HttpTransportInterface $httpTransport, $proxyConfig,
                                ContainerInterface $container)
    {
        parent::__construct($httpTransport);
        $this->proxyConfig = $proxyConfig;
        $this->container = $container;
    }

    /**
     * @inheritdoc
     * @throws \Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException
     * @throws \Mapbender\CoreBundle\Component\Exception\XmlParseException
     * @throws \Mapbender\WmtsBundle\Component\Exception\WmtsException
     */
    protected function parseResponseContent($content)
    {
        try {
            $document = WmtsCapabilitiesParser::createDocument($content);
            $source = WmtsCapabilitiesParser::getParser($document)->parse();
        } catch (NoWmtsDocument $e) {
            $document = TmsCapabilitiesParser100::createDocument($content);
            $source = TmsCapabilitiesParser100::getParser($this->proxyConfig, $document)->parse();
        }
        return new SourceLoaderResponse($source);
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
        $validator = new XmlValidator($this->container);
        $validator->validate($document);
    }
}
