<?php

namespace Mapbender\WmsBundle\Component;
use Mapbender\WmsBundle\Component\Exception\ParsingException;

/**
* Class that Parses WMS GetCapabilies Document 
* @package Mapbender
* @author Karim Malhas <karim@malhas.de>
* Parses WMS GetCapabilities documents
*/
abstract class WmsCapabilitiesParser {

    /**
     * The XML representation of the Capabilites Document
     * @var DOMDocument
     */
    protected $doc;
   
    /**
    * An Xpath-instance
    */
    protected $xpath;
    
    /**
     *
     * @param \DOMDocument $doc 
     */
    public function __construct(\DOMDocument $doc){
        $this->doc = $doc;
        $this->xpath = new \DOMXPath($doc);
        $this->xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
    } 
    protected function getValue($xpath, $contextElm=null){
        if (!$contextElm){
            $contextElm = $this->doc;
        }
        try {
            $elm = $this->xpath->query($xpath, $contextElm)->item(0);
            if($elm->nodeType == XML_ATTRIBUTE_NODE) {
                return $elm->value;
            } else if($elm->nodeType == XML_TEXT_NODE){
                return $elm->wholeText;
            } else if($elm->nodeType == XML_ELEMENT_NODE) {
                return $elm;
            } else {
                return null;
            }
        }catch(\Exception $E){
            return null;
        }
    }

    /**
     * 
     */
    abstract public function parse();

    /**
    * @param String The document to be parsed
    */
    static  function create($data, $validate = false){
        $doc = new \DOMDocument();
        if(!@$doc->loadXML($data)){
            throw new ParsingException("Could not parse CapabilitiesDocument.");
        }
        // WMS 1.0.0
        if($doc->documentElement->tagName == "WMTException"){
            $message=$doc->documentElement->nodeValue;
            throw new ParsingException($message);
        }
        
        if($doc->documentElement->tagName == "ServiceExceptionReport"){
            $message=$doc->documentElement->nodeValue;
            throw new  ParsingException($message);
        }

        $version = $doc->documentElement->getAttribute("version");
        switch($version){

            case "1.0.0":
                return  new Wms100CapabilitiesParser($doc);
            case "1.1.0":
                return  new Wms110CapabilitiesParser($doc);
            case "1.1.1":
                return  new Wms111CapabilitiesParser($doc);
            case "1.3.0":
                return  new Wms130CapabilitiesParser($doc);
            default: 
                throw new ParsingException("Could not determine WMS Version");
            break;

        }

        if($validate && !@$this->doc->validate()){
            // TODO logging
        };
    }
    
}
