<?php


namespace Mapbender\CoreBundle\Component;


use Mapbender\Component\Transport\HttpTransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class XmlValidatorService
{
    /** @var HttpTransportInterface */
    protected $httpTransport;
    /** @todo: static schema files are currently in web, in mapbender-starter; they should be part of (a) Resources package(s) */
    /** @var string */
    protected $staticSchemaPath;
    /** @var LoggerInterface */
    protected $logger;
    /** @var string */
    protected $tempDir;

    public function __construct(HttpTransportInterface $httpTransport, $staticSchemaPath, LoggerInterface $logger = null)
    {
        $this->httpTransport = $httpTransport;
        $this->staticSchemaPath = $staticSchemaPath;
        $this->logger = $logger ?: new NullLogger();
        $this->tempDir = sys_get_temp_dir() . '/mapbender/xmlvalidator';
    }

    /**
     * @param string $xml
     * @param string|false|null $staticSchemaPath
     * @throws Exception\XmlParseException
     */
    public function validateXmlString($xml, $staticSchemaPath = null)
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $this->validateDocument($doc, $staticSchemaPath);
    }

    /**
     * @param \DOMDocument $document
     * @param string|false|null $staticSchemaPath
     * @throws Exception\XmlParseException
     */
    public function validateDocument(\DOMDocument $document, $staticSchemaPath = null)
    {
        $this->getValidator($staticSchemaPath)->validate($document);
    }

    /**
     * @param string|false|null $staticSchemaPath
     * @return XmlValidator
     */
    protected function getValidator($staticSchemaPath = null)
    {
        if ($staticSchemaPath === null) {
            $staticSchemaPath = $this->staticSchemaPath;
        }
        return new XmlValidator($this->httpTransport, $this->logger, $this->tempDir, $staticSchemaPath);
    }
}
