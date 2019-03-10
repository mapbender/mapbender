<?php

namespace Mapbender\WmcBundle\Component;

use Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException;
use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\WmcBundle\Component\Exception\WmcException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class that Parses WMC Document
 * Parses WMC documents
 *
 * @author Karim Malhas
 * @author Paul Schmidt
 */
abstract class WmcParser
{
    /** @var ContainerInterface  */
    protected $container;

    /**
     * Capabilities XML document
     * @var \DOMDocument
     */
    protected $doc;

    /**
     * An Xpath-instance
     */
    protected $xpath;

    /**
     * Creates an instance
     *
     * @param ContainerInterface $container
     * @param \DOMDocument       $doc
     */
    public function __construct(ContainerInterface $container, \DOMDocument $doc)
    {
        $this->container = $container;
        $this->doc       = $doc;
        $this->xpath     = new \DOMXPath($doc);
        $this->xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
    }

    /**
     * Finds the value
     * @param string $xpath xpath expression
     * @param \DOMNode $contextElm the node to use as context for evaluating the
     * XPath expression.
     * @return string|\DOMNode|null the value of item or the selected item or null
     */
    protected function getValue($xpath, $contextElm = null)
    {
        if (!$contextElm) {
            $contextElm = $this->doc;
        }
        try {
            $elm = $this->xpath->query($xpath, $contextElm);
            if ($elm === null)
                return null;
            $elm = $elm->item(0);
            if ($elm === null)
                return null;
            if ($elm->nodeType == XML_ATTRIBUTE_NODE) {
                return $elm->value;
            } else if ($elm->nodeType == XML_TEXT_NODE) {
                return $elm->wholeText;
            } else if ($elm->nodeType == XML_ELEMENT_NODE) {
                return $elm;
            } else {
                return null;
            }
        } catch (\Exception $E) {
            return null;
        }
    }

    /**
     * Parses the capabilities document
     */
    abstract public function parse();

    /**
     * Creates a document
     *
     * @param string $data the string containing the XML
     * @param boolean $validate to validate of xml
     * @return \DOMDocument a WMC document
     * @throws XmlParseException if a GetCapabilities xml is not valid
     * @throws NotSupportedVersionException if a wmc version is not supported
     */
    public static function createDocument($data, $validate = false)
    {
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($data)) {
            throw new XmlParseException("Could not parse Wmc Document.");
        }
        return WmcParser::checkWmcDocument($doc, $validate);
    }

    /**
     * Checks the wmc xml
     *
     * @param \DOMDocument $doc the wmc xml to check
     * @param boolean $validate to validate of xml
     * @return \DOMDocument checked wmc document
     * @throws WmcException if a xml is not a wmc document.
     * @throws NotSupportedVersionException if a wmc version is not supported
     */
    private static function checkWmcDocument(\DOMDocument $doc, $validate = false)
    {
        if ($doc->documentElement->tagName !== "ViewContext") {
            throw new WmcException("Not supported Wmc Document");
        }

        if ($validate && !@$doc->validate()) {
            // TODO logging
        }

        $version = $doc->documentElement->getAttribute("version");
        if ($version !== "1.1.0") {
            throw new NotSupportedVersionException('The WMC version "' . $version . '" is not supported.');
        }
        return $doc;
    }

    /**
     * Loads a wmc document from a file
     *
     * @param string $file path to wmc document
     * @param boolean $validate to validate of xml
     * @return \DOMDocument a WMC document
     * @throws XmlParseException if a file is not a wmc document.
     */
    public static function loadDocument($file, $validate = false)
    {
        $doc = new \DOMDocument();
        if (!@$doc->load($file)) {
            throw new XmlParseException("Could not parse Wmc Document.");
        }
        return WmcParser::checkWmcDocument($doc, $validate);
    }

    /**
     * Returns a wmc parser
     *
     * @param ContainerInterface $container
     * @param \DOMDocument       $doc the WMC document
     * @return WmcParser110
     * @throws NotSupportedVersionException if a version is not supported
     */
    public static function getParser(ContainerInterface $container, \DOMDocument $doc)
    {
        $version = $doc->documentElement->getAttribute("version");
        switch ($version) {
            case "1.1.0":
                return new WmcParser110($container, $doc);
            default:
                throw new NotSupportedVersionException("Could not determine WMC Version");
                break;
        }
    }

}
