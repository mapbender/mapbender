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
 * XmlValidator validate a xml document.
 *
 * @author Paul Schmidt
 */
class XmlValidator
{
    protected $container;
    protected $dir;
    protected $proxy_config;

    public function __construct($container, array $proxy_config, $orderFromWeb = null)
    {
        $this->container = $container;
        $this->dir = $this->createDir($orderFromWeb);
        $this->proxy_config = $proxy_config;
    }

    private function getSchemas(\DOMDocument $doc)
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

    public function validate(\DOMDocument $doc)
    {
        if (isset($doc->doctype)) {// DTD
            $docH = new \DOMDocument();
            if ($this->dir !== null) {
                // @TODO load external dtd, save into $this->dir and replace DOCTYPE SYSTEM with a local path
            }
            if (!@$docH->loadXML($doc->saveXML(), LIBXML_DTDLOAD | LIBXML_DTDVALID)) {
                    throw new XmlParseException("mb.wms.repository.parser.couldnotparse");
            }
            $doc = $docH;
            if (!@$doc->validate()) { // check with DTD
                throw new XmlParseException("mb.wms.repository.parser.not_valid_dtd");
            }
        } else {
            $schemaLocations = $this->getSchemas($doc);
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
                throw new XmlParseException("mb.wms.repository.parser.not_valid_xsd" . $message);
            }
            libxml_clear_errors();
        }
        return $doc;
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
        $orderFromWeb = str_replace("/app/..", "",
            $this->container->get('kernel')->getRootDir() . '/../web/' . $orderFromWeb);
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
//                $parts = explode("/", $path);
//                $file = $this->dir . "/" . $parts[count($path) - 1];
        if (stripos($fullFileName, "file:") !== 0) {
            $file = "file:///" . $fullFileName;
        }
        $schemaLocations[$ns] = $fullFileName;
    }

    private function fileNameFromUrl($ns, $url)
    {
        $maxlength = 255;
        $maxnslength = 100;
        $nsName = preg_replace("/[^a-z0-9]/", "_", strtolower($ns));
        $nsName = substr($nsName, strlen($nsName) >= $maxnslength ? strlen($nsName) - $maxnslength : 0, $maxnslength);

        $maxurllength = $maxlength - strlen($nsName) - 1;

        $urlName = preg_replace("/[^a-z0-9]/", "_", strtolower($url));
        $urlName = substr($urlName, strlen($urlName) >= $maxurllength ? strlen($urlName) - $maxurllength : 0,
            $maxurllength);

        return $nsName . "_" . $urlName;
    }

}
