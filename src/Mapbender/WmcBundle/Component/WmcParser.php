<?php

namespace Mapbender\WmcBundle\Component;

use Mapbender\CoreBundle\Component\Exception\XmlParseException;
use Mapbender\CoreBundle\Component\Exception\NotSupportedVersionException;
use Mapbender\WmcBundle\Component\Exception\WmcException;

/**
 * Class that Parses WMC document 
 * Parses WMC documents
 * 
 * @author Paul Schmidt
 */
abstract class WmcParser
{

    /**
     * The XML representation of the WMC Document
     * @var DOMDocument
     */
    protected $doc;

    /**
     * An Xpath-instance
     */
    protected $xpath;

    /**
     * Creates an instance
     * 
     * @param \DOMDocument $doc 
     */
    public function __construct(\DOMDocument $doc)
    {
        $this->doc = $doc;
        $this->xpath = new \DOMXPath($doc);
        $this->xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
    }

    /**
     * Finds the value 
     * 
     * @param string $xpath xpath expression
     * @param \DOMNode $contextElm the node to use as context for evaluating the
     * XPath expression.
     * @return string the value of item or the selected item or null
     */
    protected function getValue($xpath, $contextElm = null)
    {
        if(!$contextElm)
        {
            $contextElm = $this->doc;
        }
        try
        {
            $elm = $this->xpath->query($xpath, $contextElm)->item(0);
            if($elm->nodeType == XML_ATTRIBUTE_NODE)
            {
                return $elm->value;
            } else if($elm->nodeType == XML_TEXT_NODE)
            {
                return $elm->wholeText;
            } else if($elm->nodeType == XML_ELEMENT_NODE)
            {
                return $elm;
            } else
            {
                return null;
            }
        } catch(\Exception $E)
        {
            return null;
        }
    }

    /**
     * Parses the WMC document
     * @return \Mapbender\WmcBundle\Entity\Wmc
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
        if(!@$doc->loadXML($data))
        {
            throw new XmlParseException("Could not parse Wmc Document.");
        }

        if($doc->documentElement->tagName !== "ViewContext")
        {
            throw new WmcException("Not supported Wmc Document");
        }

        if($validate && !@$this->doc->validate())
        {
            // TODO logging
        }

        $version = $doc->documentElement->getAttribute("version");
        if($version !== "1.1.0")
        {
            throw new NotSupportedVersionException('The WMC version "'
                    . $version . '" is not supported.');
        }
        return $doc;
    }

    /**
     * Returns a wmc parser
     * 
     * @param \DOMDocument $doc the WMC document
     * @return \Mapbender\WmsBundle\Component\WmcParser110
     * @throws NotSupportedVersionException if a version is not supported
     */
    public static function getParser(\DOMDocument $doc)
    {
        $version = $doc->documentElement->getAttribute("version");
        switch($version)
        {
            case "1.1.0":
                return new WmcParser110($doc);
            default:
                throw new NotSupportedVersionException("Could not determine WMC Version");
                break;
        }
    }

}
