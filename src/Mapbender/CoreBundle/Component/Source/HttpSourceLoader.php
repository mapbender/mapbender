<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Exception\InvalidUrlException;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\Exception\Loader\MalformedXmlException;
use Mapbender\Exception\Loader\RefreshTypeMismatchException;
use Mapbender\Exception\Loader\ServerResponseErrorException;
use Mapbender\Exception\Loader\SourceLoaderException;
use Mapbender\ManagerBundle\Form\Type\HttpSourceOriginType;
use Mapbender\ManagerBundle\Form\Type\HttpSourceSelectionType;
use Symfony\Component\HttpFoundation\Response;

abstract class HttpSourceLoader extends SourceLoader
{

    public function __construct(
        protected HttpTransportInterface $httpTransport)
    {
    }

    /**
     * @throws InvalidUrlException
     */
    abstract protected function getResponse(HttpOriginInterface $origin): Response;

    abstract public function parseResponseContent($content): Source;

    /**
     * @throws XmlParseException
     */
    abstract public function validateResponseContent(string $content): void;

    /**
     * @throws InvalidUrlException
     */
    public function loadSource(mixed $formData): Source
    {
        if (!$formData instanceof HttpOriginInterface) {
            throw new \InvalidArgumentException('Expected formData to be HttpOriginInterface, got ' . \gettype($formData));
        }

        $response = $this->getResponse($formData);
        if (!$response->isOk()) {
            // __toString is the only way to access the statusText property :(
            $statusLine = \preg_replace('#[\r\n].*$#m', '', $response->__toString());
            throw new ServerResponseErrorException($statusLine, $response->getStatusCode());
        }

        $source = $this->parseResponseContent($response->getContent());
        $this->updateOrigin($source, $formData);
        return $source;
    }

    public function getFormType(): string
    {
        return HttpSourceSelectionType::class;
    }

    /**
     * @throws XmlParseException
     * @throws InvalidUrlException
     */
    public function validateServer(HttpOriginInterface $origin): void
    {
        $response = $this->getResponse($origin);
        $this->validateResponseContent($response->getContent());
    }

    /**
     * Copies origin-related attributes (url, username, password) from $origin to $target
     */
    public static function updateOrigin(MutableHttpOriginInterface $target, HttpOriginInterface $origin)
    {
        $target->setOriginUrl($origin->getOriginUrl());
        $target->setUsername($origin->getUsername());
        $target->setPassword($origin->getPassword());
    }

    public function getRefreshUrl(Source $target): string
    {
        return $target->getOriginUrl();
    }

    /**
     * @throws SourceLoaderException
     */
    public function refresh(Source $target, HttpOriginInterface $origin): void
    {
        $reloadedSource = $this->loadSource($origin);
        $this->beforeSourceUpdate($target, $reloadedSource);
        $settings = $origin instanceof SourceLoaderSettings ? $origin : null;
        $this->updateSource($target, $reloadedSource, $settings);
        $this->updateOrigin($target, $origin);
    }

    /**
     * @throws RefreshTypeMismatchException
     */
    protected function beforeSourceUpdate(Source $target, Source $reloaded): void
    {
        if ($target->getType() !== $reloaded->getType()) {
            $message = "Source type mismatch: {$target->getType()} (old) vs {$reloaded->getType()} (reloaded)";
            throw new RefreshTypeMismatchException($message);
        }
    }

    abstract public function updateSource(Source $target, Source $reloaded, ?SourceLoaderSettings $settings = null);

    /**
     * @throws InvalidUrlException
     */
    public static function validateUrl(string $url): void
    {
        $parts = parse_url($url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            throw new InvalidUrlException($url);
        }
    }

    /**
     * @throws SourceLoaderException
     */
    protected function xmlToDom(string $content): \DOMDocument
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
