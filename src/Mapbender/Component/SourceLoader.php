<?php


namespace Mapbender\Component;


use Mapbender\Component\Loader\SourceLoaderResponse;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\Source\MutableHttpOriginInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class SourceLoader
{
    /** @var HttpTransportInterface */
    protected $httpTransport;

    public function __construct(HttpTransportInterface $httpTransport)
    {
        $this->httpTransport = $httpTransport;
    }

    /**
     * @param HttpOriginInterface $origin
     * @return Response
     * @throws InvalidUrlException
     */
    abstract protected function getResponse(HttpOriginInterface $origin);

    /**
     * @param string $content
     * @return SourceLoaderResponse
     * @throws XmlParseException
     */
    abstract protected function parseResponseContent($content);

    /**
     * @param HttpOriginInterface $origin
     * @param bool $onlyValid
     * @return SourceLoaderResponse
     * @throws XmlParseException
     * @throws InvalidUrlException
     */
    public function evaluateServer(HttpOriginInterface $origin, $onlyValid = true)
    {
        $response = $this->getResponse($origin);
        $loaderResponse = $this->parseResponseContent($response->getContent());
        $loaderResponse->getSource()->setValid(true);
        $this->updateOrigin($loaderResponse->getSource(), $origin);
        return $loaderResponse;
    }

    /**
     * Copies origin-related attributes (url, username, password) from $origin to $target
     *
     * @param MutableHttpOriginInterface $target
     * @param HttpOriginInterface $origin
     */
    public static function updateOrigin(MutableHttpOriginInterface $target, HttpOriginInterface $origin)
    {
        $target->setOriginUrl($origin->getOriginUrl());
        $target->setUsername($origin->getUserName());
        $target->setPassword($origin->getPassword());
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
}
