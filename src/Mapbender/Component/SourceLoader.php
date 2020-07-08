<?php


namespace Mapbender\Component;


use Mapbender\Component\Loader\SourceLoaderResponse;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\Source\MutableHttpOriginInterface;
use Mapbender\CoreBundle\Entity\Source;
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
     * @return Source
     * @throws XmlParseException
     */
    abstract public function parseResponseContent($content);

    /**
     * @param string $content
     * @throws XmlParseException
     */
    abstract public function validateResponseContent($content);

    /**
     * @param HttpOriginInterface $origin
     * @return SourceLoaderResponse
     * @throws XmlParseException
     * @throws InvalidUrlException
     */
    public function evaluateServer(HttpOriginInterface $origin)
    {
        $response = $this->getResponse($origin);
        $source = $this->parseResponseContent($response->getContent());
        $this->updateOrigin($source, $origin);
        return new SourceLoaderResponse($source);
    }

    /**
     * @param HttpOriginInterface $origin
     * @throws XmlParseException
     * @throws InvalidUrlException
     */
    public function validateServer(HttpOriginInterface $origin)
    {
        $response = $this->getResponse($origin);
        $this->validateResponseContent($response->getContent());
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
        $target->setUsername($origin->getUsername());
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
