<?php

namespace Mapbender\WmsBundle\Component\Wms;

use Buzz\Message\Response;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\XmlValidator;
use Mapbender\WmsBundle\Component\Wms\Importer\DeferredValidation;
use Mapbender\WmsBundle\Component\WmsCapabilitiesParser;
use Mapbender\WmsBundle\Entity\WmsOrigin;
use Mapbender\WmsBundle\Entity\WmsSource;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service class that produces WmsSource entities by evaluating a "GetCapabilities" document, either directly
 * in-memory, or from a given WmsOrigin (which is just url + username + password).
 * WmsSource is bundled in a Response class with validation errors. This is done because validation exceptions
 * can be optionally suppressed ("onlyValid"=false). In that case, the Response will contain the exception, if
 * any. By default, validation exceptions are thrown.
 *
 * An instance is registered in container as mapbender.importer.source.wms.service, see services.xml
 */
class Importer extends ContainerAware
{
    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * Performs a GetCapabilities request against WMS at $serviceOrigin and returns a WmsSource instance and the
     * (suppressed) XML validation error, if any, wrapped in a ImporterResponse object.
     *
     * @param WmsOrigin $serviceOrigin
     * @param bool $onlyValid
     * @return \Mapbender\WmsBundle\Component\Wms\Importer\Response
     * @throws XmlParseException
     */
    public function evaluateServer(WmsOrigin $serviceOrigin, $onlyValid=true)
    {
        $capsDocument = $this->loadCapabilitiesDocument($serviceOrigin);
        $response = $this->evaluateCapabilitiesDocument($capsDocument, $onlyValid);
        $this->updateOrigin($response->getWmsSourceEntity(), $serviceOrigin);
        return $response;
    }

    /**
     * Checks / evaluates a capabilities document returns a WmsSource instance and the (suppressed) XML validation error,
     * if any, wrapped in an Importer\Response object.
     *
     * @param \DOMDocument $document
     * @param boolean $onlyValid
     * @return \Mapbender\WmsBundle\Component\Wms\Importer\Response
     * @throws XmlParseException
     */
    public function evaluateCapabilitiesDocument(\DOMDocument $document, $onlyValid=true)
    {
        $parser = WmsCapabilitiesParser::getParser($document);
        if ($onlyValid) {
            $this->validate($document);
            $sourceEntity = $parser->parse();
            $sourceEntity->setValid(true);
            $validationError = null;
        } else {
            $sourceEntity = $parser->parse();
            $validationError = new DeferredValidation($sourceEntity, $document, $this);
            // valid attribute on WmsSource will be updated by deferred validation
            $sourceEntity->setValid(true);
        }
        return new Importer\Response($sourceEntity, $validationError);
    }

    /**
     * @return string[]
     */
    public static function requestDefaults()
    {
        return array(
            'request' => 'GetCapabilities',
            'service' => 'WMS',
        );
    }

    /**
     * @param WmsOrigin $serviceOrigin
     * @return \DOMDocument
     */
    public function loadCapabilitiesDocument(WmsOrigin $serviceOrigin)
    {
        static::validateUrl($serviceOrigin->getUrl());
        $serviceResponse = $this->capabilitiesRequest($serviceOrigin, static::requestDefaults());
        $capsDocument = WmsCapabilitiesParser::createDocument($serviceResponse->getContent());
        return $capsDocument;
    }

    /**
     * @param \DOMDocument $capsDocument
     * @throws XmlParseException
     */
    public function validate(\DOMDocument $capsDocument)
    {
        $validator = new XmlValidator($this->container);
        $validator->validate($capsDocument);
    }

    /**
     * @param WmsOrigin $serviceOrigin
     * @param array $params
     * @return Response
     */
    protected function capabilitiesRequest(WmsOrigin $serviceOrigin, $params)
    {
        $proxy_config = $this->container->getParameter("owsproxy.proxy");
        $proxy_query  = ProxyQuery::createFromUrl($serviceOrigin->getUrl(), $serviceOrigin->getUserName(), $serviceOrigin->getPassword());
        /** @TODO: we REQUIRE a GetCapabilities request, so this should be a forced replacement of the "request" param */
        /** @TODO: evaluate $params instead of hard-coding, so this thing actually becomes flexible enough for reuse */
        if ($proxy_query->getGetPostParamValue("request", true) === null) {
            $proxy_query->addQueryParameter("request", "GetCapabilities");
        }
        if ($proxy_query->getGetPostParamValue("service", true) === null) {
            $proxy_query->addQueryParameter("service", "WMS");
        }
        $proxy = new CommonProxy($proxy_config, $proxy_query, $this->container->get("logger"));
        /** @var Response $response */
        $response = $proxy->handle();
        return $response;
    }

    /**
     * @param string $url
     * @throws InvalidUrlException
     */
    public static function validateUrl($url)
    {
        $parts = parse_url($url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            throw new InvalidUrlException($url);
        }
    }

    /**
     * Copies origin-related attributes (url, username, password) from $origin to $wmsSource
     *
     * @param WmsSource $wmsSource
     * @param WmsOrigin $origin
     */
    public static function updateOrigin(WmsSource $wmsSource, WmsOrigin $origin)
    {
        $wmsSource->setOriginUrl($origin->getUrl());
        $wmsSource->setUsername($origin->getUserName());
        $wmsSource->setPassword($origin->getPassword());
    }
}
