<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * XmlValidator class to validate xml documents.
 *
 * @author Paul Schmidt
 */
class XmlValidator
{
    /**
     *
     * @var ContainerInterface container
     */
    protected $container;

    /**
     * @var string path to local directory for schemas, document type definitions.
     */
    protected $dir = null;

    /**
     * @var array Proxy connection parameters
     */
    protected $proxy_config;

    /**
     *
     * @var array temp files to delete
     */
    protected $filesToDelete;

    /**
     * XmlValidator constructor.
     *
     * @param  ContainerInterface $container
     * @param array               $proxy_config
     * @param  null|string        $orderFromWeb Path relative to web folder
     */
    public function __construct($container, array $proxy_config, $orderFromWeb = null)
    {
        $this->container     = $container;
        $this->dir           = $this->createDir($orderFromWeb);
        $this->proxy_config  = $proxy_config;
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
        if (isset($doc->doctype)) {// DTD
            $docH = new \DOMDocument();
            $filePath = $this->getFileName($doc->doctype->name, $doc->doctype->systemId);
            $this->isDirExists($filePath);
            if (!is_file($filePath)) {
                $proxy_query = ProxyQuery::createFromUrl($doc->doctype->systemId);
                $proxy = new CommonProxy($this->proxy_config, $proxy_query);
                try {
                    $browserResponse = $proxy->handle();
                    $content = $browserResponse->getContent();
                    file_put_contents($filePath, $content);
                } catch (\Exception $e) {
                    $this->removeFiles();
                    throw $e;
                }
            }
            $docStr = str_replace($doc->doctype->systemId, $this->addFileSchema($filePath), $doc->saveXML());
            $doc->loadXML($docStr);
            unset($docStr);
            if (!@$docH->loadXML($doc->saveXML(), LIBXML_DTDLOAD | LIBXML_DTDVALID)) {
                $this->removeFiles();
                throw new XmlParseException("mb.wms.repository.parser.couldnotparse");
            }
            $doc = $docH;
            if (!@$doc->validate()) { // check with DTD
                $this->removeFiles();
                throw new XmlParseException("mb.wms.repository.parser.not_valid_dtd");
            }
        } else {
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
                $this->removeFiles();
                throw new XmlParseException("mb.wms.repository.parser.not_valid_xsd");
            }
            libxml_clear_errors();
        }
        $this->removeFiles();
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
            $proxy_query = ProxyQuery::createFromUrl($url);
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
                $this->isDirExists($fullFileName);
                $doc->save($fullFileName);
            } catch (\Exception $e) {
                throw $e;
            }
        }
        $schemaLocations[$ns] = $this->addFileSchema($fullFileName);
    }

    /**
     * Generates a file path
     *
     * @param string $ns namespace
     * @param string $url url
     * @return string file path
     */
    private function getFileName($ns, $url)
    {
        if ($this->dir === null) {
            $tmpfile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mb3_" . time();
            $this->filesToDelete[] = $tmpfile;
            return $tmpfile;
        } else {
            $fileName = $this->fileNameFromUrl($ns, $url);
            return $this->dir . $fileName;
        }
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
    }

    /**
     * Creates the xsd schemas directory or checks it.
     *
     * @param string $orderFromWeb the path to xsd schemas directory (from "web" directory relative).
     * @return string | null the absoulute path to xsd schemas directory or null.
     */
    private function createDir($orderFromWeb)
    {
        if ($orderFromWeb === null) {
            return null;
        }
        $orderFromWeb =
            $this->normalizePath($this->container->get('kernel')->getRootDir() . '/../web/' . $orderFromWeb);
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
     * Creates a new file name form namespace and url
     *
     * @param string $ns namespace
     * @param string $url url
     * @return string filename from a namespace and a url
     */
    private function fileNameFromUrl($ns, $url)
    {
        $urlArr = parse_url($url);
        if (!isset($urlArr['host'])) {
            $nsArr = parse_url($ns);
            $path   = $nsArr['host'] . $nsArr['path'];
            $path   = (strrpos($path, "/") === strlen($path) - 1 ? $path : $path . "/") . $urlArr['path'];
        } else {
            $path   = $urlArr['host'] . $urlArr['path'];
        }
        $aa = $this->normalizePath($path);
        return $this->normalizePath($path);
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
     * Checks, if a file directory exists, otherwise creates a file directory.
     * @param string $filePath the file path
     * @return boolean true if directory exists
     */
    private function isDirExists($filePath)
    {
        if (file_exists($filePath)) {
            if (is_file($filePath)) {
                return true;
            } elseif (is_dir($filePath)) {
                return rmdir($filePath);
            } else {
                return true;
            }
        } else {
            mkdir($filePath, 0777, true);
            return $this->isDirExists($filePath);
        }
    }
}
