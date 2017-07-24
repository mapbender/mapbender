<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Mapbender\CoreBundle\Component;

use Buzz\Message\Response;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * XmlValidator class to validate xml documents.
 *
 * @author Paul Schmidt
 */
class XmlValidator
{
    /** @var ContainerInterface container */
    protected $container;

    /**
     * @var string path to local directory for schemas, document type definitions.
     */
    protected $schemaCacheDir = null;

    /**
     * @var array Proxy connection parameters
     */
    protected $proxy_config;

    /**
     *
     * @var array temp files to delete
     */
    protected $filesToDelete;

    public function __construct(ContainerInterface $container, array $proxy_config)
    {
        $this->container = $container;
        $this->schemaCacheDir = $container->getParameter('kernel.cache_dir') . '/xmlschemas';
        $this->ensureDirectory($this->schemaCacheDir);
        $this->proxy_config = $proxy_config;
        $this->filesToDelete = array();
    }

    /**
     * Validates a xml document
     *
     * @param \DOMDocument $doc a xml dicument
     * @return \DOMDocument the validated xml document
     * @throws \Exception
     * @throws XmlParseException
     */
    public function validate(\DOMDocument $doc)
    {
        $this->filesToDelete = array();
        try {
            if (isset($doc->doctype)) {// DTD
                $this->validateDtd($doc);
            } else {
                $this->validateNonDtd($doc);
            }
        } catch (\Exception $e) {
            $this->removeFiles();
            throw $e;
        }
        $this->removeFiles();
        /**
         * @todo: return value is === passed argument and not used at any calling site in mapbender itself. Evaluate
         * if it's safe to remove return
         */
        return $doc;
    }


    protected function validateDtd(\DOMDocument $doc)
    {
        $docH = new \DOMDocument();
        $filePath = $this->ensureLocalSchema($doc->doctype->name, $doc->doctype->systemId);
        $docStr = str_replace($doc->doctype->systemId, $this->addFileSchema($filePath), $doc->saveXML());
        $doc->loadXML($docStr);
        unset($docStr);
        if (!@$docH->loadXML($doc->saveXML(), LIBXML_DTDLOAD | LIBXML_DTDVALID)) {
            throw new XmlParseException("mb.wms.repository.parser.couldnotparse");
        }
        $doc = $docH;
        if (!@$doc->validate()) { // check with DTD
            throw new XmlParseException("mb.wms.repository.parser.not_valid_dtd");
        }
    }

    protected function validateNonDtd(\DOMDocument $doc)
    {
        $schemaLocations = $this->addSchemas($doc);
        $imports = "";
        foreach ($schemaLocations as $namespace => $location) {
            $imports .=
                sprintf('  <xsd:import namespace="%s" schemaLocation="%s" />' . "\n", $namespace, $location);
        }

        $source = <<<EOF
<?xml version="1.0" encoding="utf-8" ?>
<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified">
<xsd:import namespace="http://www.w3.org/XML/1998/namespace"/>
$imports
</xsd:schema>
EOF
        ;
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $valid = $doc->schemaValidateSource($source);
        if (!$valid) {
            $errors = libxml_get_errors();
            $message = "";
            foreach ($errors as $error) {
                $message .= "\n" . $error->message;
            }
            $this->container->get('logger')->err($message);
            libxml_clear_errors();
            throw new XmlParseException("mb.wms.repository.parser.not_valid_xsd");
        }
        libxml_clear_errors();
    }

    /**
     * Returns namespaces and locations as array
     *
     * @param \DOMDocument $doc
     * @return array schema locations
     */
    private function addSchemas(\DOMDocument $doc)
    {
        $schemaLocations = array();
        if ($element =
            $doc->documentElement->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation')) {
            $items = preg_split('/\s+/', $element);
            for ($i = 1, $nb = count($items); $i < $nb; $i += 2) {
                $this->addSchemaLocation($schemaLocations, $items[$i - 1], $items[$i]);
            }
        }
        return $schemaLocations;
    }

    /**
     * Adds namespace and location to schema location array.
     *
     * @param array $schemaLocations schema locations
     * @param string $ns namespace
     * @param string $path url
     * @return boolean true if a schema location added otherwise false
     */
    private function addSchemaLocation(&$schemaLocations, $ns, $path)
    {
        if (stripos($path, "http:") === 0) {
            $this->addSchemaLocationReq($schemaLocations, $ns, $path);
            return true;
        } elseif (is_file($path)) {
            $schemaLocations[$ns] = $this->addFileSchema($path);
            return true;
        }
        return false;
    }

    /**
     * Loads an external xml schema, saves it local and adds a local path into a schemaLocation.
     *
     * @param array $schemaLocations schema locations
     * @param string $ns namespace
     * @param string $url path or url
     * @throws \Exception  create exception
     * @throws XmlParseException xml parse exception
     */
    private function addSchemaLocationReq(&$schemaLocations, $ns, $url)
    {
        $fullFileName = $this->getFileName($ns, $url);
        if (!is_file($fullFileName)) {
            $content = $this->download($url);
            $doc = new \DOMDocument();
            if (!@$doc->loadXML($content)) {
                throw new XmlParseException("mb.core.xmlvalidator.couldnotcreate");
            }
            $root = $doc->documentElement;
            $imports = $root->getElementsByTagName("import");
            foreach ($imports as $import) {
                /** @var \DOMElement $import */
                $ns_ = $import->getAttribute("namespace");
                $sl_ = $import->getAttribute("schemaLocation");
                $this->addSchemaLocationReq($schemaLocations, $ns_, $sl_);
            }
            $this->ensureDirectory(dirname($fullFileName));
            $doc->save($fullFileName);
        }
        $schemaLocations[$ns] = $this->addFileSchema($fullFileName);
    }

    /**
     * Removes all xsd, dtd temp files
     */
    private function removeFiles()
    {
        foreach ($this->filesToDelete as $fileToDel) {
            if (is_file($fileToDel)) {
                unlink($fileToDel);
            }
        }
        $this->filesToDelete = array();
    }

    /**
     * Generates a local file path for schema storage from namespace and url.
     *
     * @param string $ns namespace
     * @param string $url url
     * @return string filename from a namespace and a url
     */
    private function getFileName($ns, $url)
    {
        $urlArr = parse_url($url);
        if (!isset($urlArr['host'])) {
            $nsArr = parse_url($ns);
            $path   = $nsArr['host'] . $nsArr['path'];
            $path   = rtrim($path, "/") . "/" . $urlArr['path'];
        } else {
            $path   = $urlArr['host'] . $urlArr['path'];
        }
        return $this->schemaCacheDir . "/" . $this->normalizePath($path);
    }

    /**
     * Normalizes a file path: repaces all strings "/ORDERNAME/.." with "".
     *
     * @param string $path
     * @return string a mormalized file path.
     */
    private function normalizePath($path)
    {
        $path = preg_replace("/[\/\\\][^\/\\\]+[\/\\\][\.]{2}/", "", $path);
        if (!strpos($path, "..")) {
            return preg_replace("/[\/\\\]/", DIRECTORY_SEPARATOR, $path);
        } else {
            return $this->normalizePath($path);
        }
    }

    /**
     * Adds a schema "file:///" to file path.
     *
     * @param string $filePath a file path
     * @return string a file path as url
     */
    private function addFileSchema($filePath)
    {
        $filePath_ = preg_replace("/[\/\\\]/", "/", $filePath);
        if (stripos($filePath_, "file:") !== 0) {
            return "file:///" . $filePath_;
        } else {
            return $filePath_;
        }
    }

    /**
     * Creates directory $path (including parents) if not present.
     * If $path exists but is a regular file, it will be deleted first.
     * @param string $path
     */
    protected function ensureDirectory($path)
    {
        while (is_link($path)) {
            $path = readlink($path);
        }
        $wrongType = (is_file($path) ? "file" : (is_link($path) ? "symlink" : ""));
        if ($wrongType) {
            $this->getLogger()->warning("Need directory at " . var_export($path, true) . ", found $wrongType => deleting");
            unlink($path);
        }
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if (!(is_dir($path) && is_writable($path))) {
            throw new \RuntimeException("Failed to create writable directory at " . var_export($path, true));
        }
    }

    /**
     * Downloads a local copy of a schema document if not present already, and returns a local file path
     * to it.
     *
     * @param string $namespace
     * @param string $url url
     * @return string file path
     * @throws \Exception on failure
     */
    protected function ensureLocalSchema($namespace, $url)
    {
        $localPath = $this->getFileName($namespace, $url);
        if (!is_file($localPath)) {
            $schemaBody = $this->download($url);
            $this->ensureDirectory(dirname($localPath));
            file_put_contents($localPath, $schemaBody);
        }
        return $localPath;
    }

    /**
     * @param string $url
     * @return string response body
     */
    protected function download($url)
    {
        $proxy_query = ProxyQuery::createFromUrl($url);
        $proxy = new CommonProxy($this->proxy_config, $proxy_query);
        /** @var Response $response */
        $response = $proxy->handle();
        return $response->getContent();
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        /** @var LoggerInterface $logger */
        $logger = $this->container->get("logger");
        return $logger;
    }
}
