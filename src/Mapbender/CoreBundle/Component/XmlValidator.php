<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Psr\Log\LoggerInterface;

/**
 * XmlValidator class to validate xml documents.
 *
 * @author Paul Schmidt
 *
 * Legacy mix of service and transient runtime data. Do not instantiate directly.
 * Use XmlValidatorService as a frontend.
 * @internal
 */
class XmlValidator
{
    /** @var LoggerInterface */
    protected $logger;
    /** @var HttpTransportInterface */
    protected $httpTransport;

    /**
     * @var string|false path to built-in schemas used for the validation session. This is an optimization, avoiding ad-hoc
     *   downloads of commonly used schemas
     */
    protected $shippingSchemaDir = null;

    /**
     * @var string path to local directory for schemas, document type definitions.
     */
    protected $schemaDownloadDir = null;

    /**
     *
     * @var array temp files to delete
     */
    protected $filesToDelete;

    /**
     * @param HttpTransportInterface $httpTransport
     * @param LoggerInterface $logger
     * @param string $tempDir
     * @param string|null|false $staticSchemaPath
     */
    public function __construct(HttpTransportInterface $httpTransport, LoggerInterface $logger,
                                $tempDir, $staticSchemaPath = null)
    {
        $this->logger = $logger;
        $this->httpTransport = $httpTransport;
        $this->schemaDownloadDir = $this->ensureDirectory($tempDir);
        if ($staticSchemaPath) {
            $this->shippingSchemaDir = $this->normalizePath($staticSchemaPath);
        } else {
            $this->shippingSchemaDir = false;
        }
        $this->filesToDelete = array();
    }

    /**
     * Validates a xml document
     *
     * @param \DOMDocument $doc a xml dicument
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
    }


    /**
     * @param \DOMDocument $doc
     * @throws XmlParseException
     */
    protected function validateDtd(\DOMDocument $doc)
    {
        $docH = new \DOMDocument();
        $filePath = $this->ensureLocalSchema($doc->doctype->name, $doc->doctype->systemId);
        $docStr = str_replace($doc->doctype->systemId, $this->addFileSchema($filePath), $doc->saveXML());

        if (!@$docH->loadXML($docStr, LIBXML_DTDLOAD | LIBXML_DTDVALID)) {
            throw new XmlParseException("mb.wms.repository.parser.couldnotparse");
        }

        if (!@$docH->validate()) { // check with DTD
            throw new XmlParseException('mb.manager.invalid_xml');
        }
    }

    /**
     * @param \DOMDocument $doc
     * @throws XmlParseException
     */
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
        // suppress otherwise uncatchable DNS errors
        $valid = @$doc->schemaValidateSource($source);
        if (!$valid) {
            $errors = libxml_get_errors();
            $message = "";
            foreach ($errors as $error) {
                $message .= "\n" . $error->message;
            }
            $this->logger->error($message);
            libxml_clear_errors();
            throw new XmlParseException('mb.manager.invalid_xml');
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
        if (stripos($path, "http:") === 0 || stripos($path, "https:") === 0) {
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
                $schemaUrl = $this->resolveRelativeUrl($ns, $sl_);
                $this->addSchemaLocationReq($schemaLocations, $ns_, $schemaUrl);
            }
            $this->ensureDirectory(dirname($fullFileName));
            $doc->save($fullFileName);
        }
        $schemaLocations[$ns] = $this->addFileSchema($fullFileName);
    }

    /**
     * Turn a relative URL back into an absolute URL based on a context URL.
     * This fixes downloading errors on e.g. http://inspire.ec.europa.eu/schemas/inspire_vs/1.0/inspire_vs.xsd
     * which contains a schemaLocation="../../common/1.0/common.xsd" relative reference.
     *
     * @param string $contextUrl
     * @param string $path
     * @return string
     */
    protected function resolveRelativeUrl($contextUrl, $path)
    {
        $absolutePattern = '#^[\w]+://#';
        $isAbsolute = !!preg_match($absolutePattern, $path);
        if ($isAbsolute) {
            return $path;
        }
        if (!preg_match($absolutePattern, $contextUrl)) {
            throw new \RuntimeException("Context url is not absolute: " . var_export($contextUrl, true));
        }
        if (stripos($contextUrl, 'file:') === 0) {
            throw new \RuntimeException("Context url is a file: " . var_export($contextUrl, true));
        }
        // @todo: support "//different-host/..." form for same protocol
        // @todo: support "/absolute/path" form for same host and protocol
        $contextParts = explode('/', $contextUrl);
        $pathParts = explode('/', $path);
        foreach ($pathParts as $i => $part) {
            if ($part == '..') {
                $contextParts = array_slice($contextParts, 0, -1);
            } else {
                $contextParts[] = $part;
            }
        }
        $reconstructed = implode('/', $contextParts);

        return $reconstructed;
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
        // try in shipping schema dir, and return the path if that file exists
        // otherwise, return a file name in download dir, and track it for deletion
        $path = $this->normalizePath($path);
        $shippingPath = $this->locateShippingSchema($ns, $path, $url);
        if ($shippingPath && file_exists($shippingPath)) {
            return $shippingPath;
        } else {
            $downloadPath = $this->schemaDownloadDir . "/{$path}";
            // this file needs to be cleaned up later
            $this->filesToDelete[] = $downloadPath;
            return $downloadPath;
        }
    }

    /**
     * Removes parent directory traversal from a path by removing all "<parent name>/.." occurences with "".
     * Supports both Unix and Windows style directory separators.
     *
     * @param string $path
     * @return string a mormalized file path with native directory separators
     */
    private function normalizePath($path)
    {
        $path = preg_replace('#[/\\\\][^/\\\\]+[/\\\\][\.]{2}#', '', $path);
        if (!strpos($path, "..")) {
            return preg_replace('#[/\\\\]#', DIRECTORY_SEPARATOR, $path);
        } else {
            return $this->normalizePath($path);
        }
    }

    /**
     * Adds a schema "file:///" to file path, enforces Unix-style directory separators.
     *
     * @param string $filePath a file path
     * @return string a file path as url
     */
    private function addFileSchema($filePath)
    {
        $filePath_ = preg_replace('#[/\\\\]#', '/', $filePath);
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
     * @return string absolute, final path (symlinks resolved)
     */
    protected function ensureDirectory($path)
    {
        while (is_link($path)) {
            $path = readlink($path);
        }
        $wrongType = (is_file($path) ? "file" : (is_link($path) ? "symlink" : ""));
        if ($wrongType) {
            $this->logger->warning("Need directory at " . var_export($path, true) . ", found $wrongType => deleting");
            unlink($path);
        }
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if (!(is_dir($path) && is_writable($path))) {
            throw new \RuntimeException("Failed to create writable directory at " . var_export($path, true));
        }
        return $path;
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
        return $this->httpTransport->getUrl($url)->getContent();
    }

    /**
     * Finds an appropriate schema file in the "shipping" / bundled set, so we can avoid repeated downloads of
     * common, static schemas.
     *
     * @param string $targetNamespace the schema namespace
     * @param string $initialPath a relative sub-path, e.g. 'schemas.opengis.net/wms/1.3.0/capabilities_1_3_0.xsd'
     * @param string $url the entire request url for the scheme, used as a fallback for legacy file placement
     * @return string|null absoulte path to local file or null if not found
     */
    protected function locateShippingSchema($targetNamespace, $initialPath, $url)
    {
        $newStyleFullPath = "{$this->shippingSchemaDir}/{$initialPath}";
        if (file_exists($newStyleFullPath)) {
            return $newStyleFullPath;
        } else {
            // Legacy file naming placed all files into a single directory by flattening directory separators into
            // underscores. Any other non-word characters (dots, '://' etc) are also converted to underscores.
            /**
             * @todo: shipping schemas should relocate and adopt a single file naming convention that works on
             *        Unix and Windows. Then we can remove this entire path.
             */
            $legacyCandidates = array(
                // first form will use the full URL, most notably including the scheme
                // this produces names like
                //   "xmlschemas/http___www_w3_org_XML_1998_namespace_http___www_w3_org_2001_xml_xsd"
                "{$targetNamespace}_{$url}",
                // second form uses the (already preprocessed) path
                "{$targetNamespace}_{$initialPath}",
            );
            foreach ($legacyCandidates as $candidate) {
                // flatten non-word / non-digit chars to underscores, prepend base path
                $underscored = preg_replace('#[^\w\d]#', '_', $candidate);
                $fullPath = "{$this->shippingSchemaDir}/{$underscored}";
                if (file_exists($fullPath)) {
                    return $fullPath;
                }
            }
            return null;
        }
    }
}
