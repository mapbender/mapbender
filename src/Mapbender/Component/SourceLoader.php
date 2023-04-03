<?php


namespace Mapbender\Component;


use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\Source\MutableHttpOriginInterface;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\Exception\Loader\MalformedXmlException;
use Mapbender\Exception\Loader\RefreshTypeMismatchException;
use Mapbender\Exception\Loader\ServerResponseErrorException;
use Mapbender\Exception\Loader\SourceLoaderException;
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
     * @return string
     */
    abstract public function getTypeLabel();

    /**
     * @return string
     */
    abstract public function getTypeCode();

    /**
     * @param HttpOriginInterface $origin
     * @return Response
     * @throws InvalidUrlException
     */
    abstract protected function getResponse(HttpOriginInterface $origin);

    /**
     * @param string $content
     * @return Source
     */
    abstract public function parseResponseContent($content);

    /**
     * @param string $content
     * @throws XmlParseException
     */
    abstract public function validateResponseContent($content);

    /**
     * @param HttpOriginInterface $origin
     * @return Source
     * @throws InvalidUrlException
     */
    public function evaluateServer(HttpOriginInterface $origin)
    {
        $response = $this->getResponse($origin);
        if (!$response->isOk()) {
            // __toString is the only way to access the statusText property :(
            $statusLine = \preg_replace('#[\r\n].*$#m', '', $response->__toString());
            throw new ServerResponseErrorException($statusLine, $response->getStatusCode());
        }

        $source = $this->parseResponseContent($response->getContent());
        $this->updateOrigin($source, $origin);
        return $source;
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

    public function getRefreshUrl(Source $target)
    {
        return $target->getOriginUrl();
    }

    /**
     * @param Source $target
     * @param HttpOriginInterface $origin
     * @throws SourceLoaderException
     */
    public function refresh(Source $target, HttpOriginInterface $origin)
    {
        $reloadedSource = $this->evaluateServer($origin);
        $this->beforeSourceUpdate($target, $reloadedSource);
        $settings = $origin instanceof SourceLoaderSettings ? $origin : null;
        $this->updateSource($target, $reloadedSource, $settings);
        $this->updateOrigin($target, $origin);
    }

    /**
     * @param Source $target
     * @param Source $reloaded
     * @throws RefreshTypeMismatchException
     */
    protected function beforeSourceUpdate(Source $target, Source $reloaded)
    {
        if ($target->getType() !== $reloaded->getType()) {
            $message = "Source type mismatch: {$target->getType()} (old) vs {$reloaded->getType()} (reloaded)";
            throw new RefreshTypeMismatchException($message);
        }
    }

    abstract public function updateSource(Source $target, Source $reloaded, ?SourceLoaderSettings $settings = null);

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
     * @param string $content
     * @return \DOMDocument
     * @throws SourceLoaderException
     */
    protected function xmlToDom($content)
    {
        $doc = new \DOMDocument();
        try {
            $xmlSuccess = $doc->loadXML($content);
        } catch (\ErrorException $e) {
            $message = \preg_replace('#^.*?::loadXml\(\):\s+#i', '', $e->getMessage());
            throw new MalformedXmlException($content, $message, $e->getCode(), $e);
        }

        if (!$xmlSuccess || !$doc->documentElement) {
            throw new MalformedXmlException($content);
        }
        if (false !== \stripos($doc->documentElement->tagName, 'Exception')) {
            // @todo: use a different exception to indicate server response failure
            // @todo: Show the user the server's error message
            throw new ServerResponseErrorException($doc->documentElement->textContent);
        }
        return $doc;
    }
}
