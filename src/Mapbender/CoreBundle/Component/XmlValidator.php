<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;

/**
 * XmlValidator class to validate xml documents.
 *
 * @author Paul Schmidt
 */
class XmlValidator
{
    /**
     *
     * @var type container
     */
    protected $container;
    /**
     * @var string path to local directory for schemas, document type definitions.
     */
    protected $dir;
    /**
     * @var array Proxy connection parameters
     */
    protected $proxy_config;

    public function __construct($container, array $proxy_config, $orderFromWeb = null)
    {
        $this->container = $container;
        $this->dir = $this->createDir($orderFromWeb);
        $this->proxy_config = $proxy_config;
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
        if (isset($doc->doctype)) {// DTD
            $docH = new \DOMDocument();
            if ($this->dir !== null) {
                $filePath = $this->addFileSchema($this->dir . $this->fileNameFromUrl($doc->doctype->name,
                        $doc->doctype->systemId));
                if (!is_file($filePath)) {
                    $proxy_query = ProxyQuery::createFromUrl($doc->doctype->systemId);
                    $proxy = new CommonProxy($this->proxy_config, $proxy_query);
                    try {
                        $browserResponse = $proxy->handle();
                        $content = $browserResponse->getContent();
                        file_put_contents($filePath, $content);
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
                $docStr = str_replace($doc->doctype->systemId, $filePath, $doc->saveXML());
                $doc->loadXML($docStr);
                unset($docStr);
            }
            if (!@$docH->loadXML($doc->saveXML(), LIBXML_DTDLOAD | LIBXML_DTDVALID)) {
                throw new XmlParseException("mb.wms.repository.parser.couldnotparse");
            }
            $doc = $docH;
            if (!@$doc->validate()) { // check with DTD
                throw new XmlParseException("mb.wms.repository.parser.not_valid_dtd");
            }
        } else {
            $schemaLocations = $this->addSchemas($doc);
            $imports = "";
            foreach ($schemaLocations as $namespace => $location) {
                $imports .= sprintf('  <xsd:import namespace="%s" schemaLocation="%s" />' . "\n", $namespace, $location);
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
                throw new XmlParseException("mb.wms.repository.parser.not_valid_xsd");
            }
            libxml_clear_errors();
        }
        return $doc;
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
        if ($element = $doc->documentElement->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance',
            'schemaLocation')) {
            $items = preg_split('/\s+/', $element);
            for ($i = 1, $nb = count($items); $i < $nb; $i += 2) {
                $this->addSchemaLocation($schemaLocations, $items[$i - 1], $items[$i]);
            }
        }
        return $schemaLocations;
    }

    /**
     * Creates the xsd schemas directory or checks it. 
     * 
     * @param string $orderFromWeb the path to xsd schemas directory (from "web" directory relative).
     * @return string | null the absoulute path to xsd schemas directory or null.
     */
    private function createDir($orderFromWeb)
    {
        if ($orderFromWeb === null)
            return null;
        $orderFromWeb = $this->normalizePath($this->container->get('kernel')->getRootDir() . '/../web/' . $orderFromWeb);
        if (!is_dir($orderFromWeb)) {
            if (mkdir($orderFromWeb)) {
                return $orderFromWeb;
            } else {
                return null;
            }
        } else {
            return $orderFromWeb;
        }
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
        if ($this->dir === null) {
            if (stripos($path, "http://") === 0) {
                $schemaLocations[$ns] = $path;
                return true;
            }
        } else {
            if (stripos($path, "http:") === 0) {
                $this->addSchemaLocationReq($schemaLocations, $ns, $path);
                return true;
            } else if (is_file($path)) {
                $schemaLocations[$ns] = $path;
                return true;
            }
        }
        return false;
    }

    /**
     * Loads an external xml schema, saves it local and adds a local path into a schemaLocation. 
     * 
     * @param array $schemaLocations schema locations
     * @param string $ns namespace
     * @param string $path path or url
     * @throws \Exception  create exception
     * @throws XmlParseException xml parse exception
     */
    private function addSchemaLocationReq(&$schemaLocations, $ns, $path)
    {
        $fileName = $this->fileNameFromUrl($ns, $path);
        $fullFileName = $this->dir . $fileName;
        if (!is_file($fullFileName)) {
            $proxy_query = ProxyQuery::createFromUrl($path);
            $proxy = new CommonProxy($this->proxy_config, $proxy_query);
            try {
                $browserResponse = $proxy->handle();
                $content = $browserResponse->getContent();
                $doc = new \DOMDocument();
                if (!@$doc->loadXML($content)) {
                    throw new XmlParseException("mb.core.xmlvalidator.couldnotcreate");
                }
                $root = $doc->documentElement;
                $imports = $root->getElementsByTagName("import");
                foreach ($imports as $import) {
                    $ns_ = $import->getAttribute("namespace");
                    $sl_ = $import->getAttribute("schemaLocation");
                    $this->addSchemaLocationReq($schemaLocations, $ns_, $sl_);
                }
                $doc->save($fullFileName);
            } catch (\Exception $e) {
                throw $e;
            }
        }
        $schemaLocations[$ns] = $this->addFileSchema($fullFileName);
    }

    /**
     * Creates a new file name form namespace and url
     * 
     * @param string $ns namespace
     * @param string $url url
     * @return string filename from a namespace and a url
     */
    private function fileNameFromUrl($ns, $url)
    {
        $maxlength = 255;
        if (strlen($ns . $url) + 1 <= $maxlength) {
            $nsName = preg_replace("/[^A-Za-z0-9]/", "_", $ns);
            $urlName = preg_replace("/[^A-Za-z0-9]/", "_", $url);
        } else {
            $maxnslength = 100;
            $nsName = preg_replace("/[^A-Za-z0-9]/", "_", $ns);
            $nsName = substr($nsName, strlen($nsName) >= $maxnslength ? strlen($nsName) - $maxnslength : 0, $maxnslength);

            $maxurllength = $maxlength - strlen($nsName) - 1;

            $urlName = preg_replace("/[^A-Za-z0-9]/", "_", $url);
            $urlName = substr($urlName, strlen($urlName) >= $maxurllength ? strlen($urlName) - $maxurllength : 0,
                $maxurllength);
        }
        // TODO OS Windows file name strtolower??
        return $this->normalizePath($nsName . "_" . $urlName);
    }

    /**
     * Normalizes a file path: repaces all strings "/ORDERNAME/.." with "".
     *  
     * @param string $path
     * @return string a mormalized file path.
     */
    private function normalizePath($path)
    {
        // TODO replace separator in $path with OS file separator ???
        $path = preg_replace("/[\/\\\][^\/\\\]+[\/\\\][\.]{2}/", "", $path);
        if (!strpos($path, "..")) {
            return $path;
        } else {
            $this->normalizePath($path);
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
        if (stripos($filePath, "file:") !== 0) {
            return "file:///" . $filePath;
        } else {
            return $filePath;
        }
    }

}
