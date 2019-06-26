<?php

namespace Mapbender\WmsBundle\Component\Wms;

use Mapbender\Component\SourceLoader;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\XmlValidator;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\Wms\Importer\DeferredValidation;
use Mapbender\WmsBundle\Component\WmsCapabilitiesParser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service class that produces WmsSource entities by evaluating a "GetCapabilities" document, either directly
 * in-memory, or from a given WmsOrigin (which is just url + username + password).
 * WmsSource is bundled in a Response class with validation errors. This is done because validation exceptions
 * can be optionally suppressed ("onlyValid"=false). In that case, the Response will contain the exception, if
 * any. By default, validation exceptions are thrown.
 *
 * An instance is registered in container as mapbender.importer.source.wms.service, see services.xml
 */
class Importer extends SourceLoader
{

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param HttpTransportInterface $transport
     * @param ContainerInterface $container
     */
    public function __construct(HttpTransportInterface $transport, ContainerInterface $container)
    {
        parent::__construct($transport);
        $this->container = $container;
    }

    /**
     * @inheritdoc
     * @throws InvalidUrlException
     */
    protected function getResponse(HttpOriginInterface $origin)
    {
        static::validateUrl($origin->getOriginUrl());
        return $this->capabilitiesRequest($origin);
    }

    protected function parseResponseContent($content)
    {
        $document = WmsCapabilitiesParser::createDocument($content);
        $parser = WmsCapabilitiesParser::getParser($document);
        return new Importer\Response($parser->parse($document), $document);
    }

    /**
     * Performs a GetCapabilities request against WMS at $serviceOrigin and returns a WmsSource instance and the
     * (suppressed) XML validation error, if any, wrapped in a ImporterResponse object.
     *
     * @param HttpOriginInterface $serviceOrigin
     * @param bool $onlyValid
     * @return \Mapbender\WmsBundle\Component\Wms\Importer\Response
     * @throws \Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException
     * @throws \Mapbender\WmsBundle\Component\Exception\WmsException
     */
    public function evaluateServer(HttpOriginInterface $serviceOrigin, $onlyValid=true)
    {
        /** @var Importer\Response $response */
        $response = parent::evaluateServer($serviceOrigin, $onlyValid);
        if ($onlyValid) {
            $validationError = new DeferredValidation($response->getSource(), $response->getDocument(), $this);
        } else {
            $validationError = null;
        }
        return new Importer\Response($response->getSource(), $response->getDocument(), $validationError);
    }

    /**
     * Checks / evaluates a capabilities document returns a WmsSource instance and the (suppressed) XML validation error,
     * if any, wrapped in an Importer\Response object.
     *
     * @param \DOMDocument $document
     * @param boolean $onlyValid
     * @return \Mapbender\WmsBundle\Component\Wms\Importer\Response
     * @throws XmlParseException
     * @throws \Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException
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
        return new Importer\Response($sourceEntity, $document, $validationError);
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
     * @param HttpOriginInterface $serviceOrigin
     * @return Response
     */
    protected function capabilitiesRequest(HttpOriginInterface $serviceOrigin)
    {
        $addParams = array();
        $url = $serviceOrigin->getOriginUrl();
        $addParams['REQUEST'] = 'GetCapabilities';
        if (!UrlUtil::getQueryParameterCaseInsensitive($url, 'service')) {
            $addParams['SERVICE'] = 'WMS';
        }
        $url = UrlUtil::validateUrl($url, $addParams);
        $url = UrlUtil::addCredentials($url, $serviceOrigin->getUserName(), $serviceOrigin->getPassword());
        return $this->httpTransport->getUrl($url);
    }
}
