<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Component;

/**
 * The UrlTemplateType describes:
 * URL template to a tile or a FeatureInfo resource on resource oriented architectural style.
 * @author Paul Schmidt
 */
class UrlTemplateType
{

    /**
     * @var string Format of the resource representation that can be retrieved one resolved the URL template
     * (ows:MimeType, required).
     */
    public $format;

    /**
     * @var string Resource type to be retrieved. It can only be "tile" or "FeatureInfo" (required).
     */
    public $resourceType;

    /**
     *
     * @var string URL template. A template processor will be applied to substitute some variables between {}
     *  for their values and get a URL to a resource. We cound not use a anyURI type (that conforms the character
     * restrictions specified in RFC2396 and excludes '{' '}' characters in some XML parsers) because this
     * attribute must accept the '{' '}' caracters.
     * pattern ([A-Za-z0-9\-_\.!~\*'\(\);/\?:@\+:$,#\{\}=&amp;]|%[A-Fa-f0-9][A-Fa-f0-9])+
     */
    public $template;

    /**
     * Get format
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Gets resourceType
     * @return string
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * Gets template.
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Sets format
     * @param string $format
     * @return \Mapbender\WmtsBundle\Component\UrlTemplateType
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Sets resourceType
     * @param type $resourceType
     * @return \Mapbender\WmtsBundle\Component\UrlTemplateType
     */
    public function setResourceType($resourceType)
    {
        $this->resourceType = $resourceType;
        return $this;
    }

    /**
     * Sets template
     * @param type $template
     * @return \Mapbender\WmtsBundle\Component\UrlTemplateType
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }
}
