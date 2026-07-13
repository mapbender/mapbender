<?php


namespace Mapbender\CoreBundle\Component;


use Mapbender\Component\Transport\HttpTransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class XmlValidatorService
{
    protected LoggerInterface $logger;
    protected string $tempDir;

    /**
     * @param string $staticSchemaPath
     */
    public function __construct(protected HttpTransportInterface $httpTransport, protected $staticSchemaPath, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
        $this->tempDir = sys_get_temp_dir() . '/mapbender/xmlvalidator';
    }

    /**
     * @param string $xml
     * @param string|false|null $staticSchemaPath
     * @throws Exception\XmlParseException
     */
    public function validateXmlString($xml, $staticSchemaPath = null): void
    {
        $doc = new \DOMDocument();
        // Security: LIBXML_NONET disables network access during XML parsing to prevent XXE attacks
        $doc->loadXML($xml, LIBXML_NONET);
        $this->validateDocument($doc, $staticSchemaPath);
    }

    /**
     * @param \DOMDocument $document
     * @param string|false|null $staticSchemaPath
     * @throws Exception\XmlParseException
     */
    public function validateDocument(\DOMDocument $document, $staticSchemaPath = null): void
    {
        $this->getValidator($staticSchemaPath)->validate($document);
    }

    /**
     * @param string|false|null $staticSchemaPath
     * @return XmlValidator
     */
    protected function getValidator($staticSchemaPath = null): XmlValidator
    {
        if ($staticSchemaPath === null) {
            $staticSchemaPath = $this->staticSchemaPath;
        }
        return new XmlValidator($this->httpTransport, $this->logger, $this->tempDir, $staticSchemaPath);
    }
}
