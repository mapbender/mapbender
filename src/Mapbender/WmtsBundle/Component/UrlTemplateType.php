<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;

/**
 * The UrlTemplateType describes:
 * URL template to a tile or a FeatureInfo resource on resource oriented architectural style.
 * @author Paul Schmidt
 */
class UrlTemplateType implements MutableUrlTarget
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
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Sets resourceType
     * @param string $resourceType
     * @return $this
     */
    public function setResourceType($resourceType)
    {
        $this->resourceType = $resourceType;
        return $this;
    }

    /**
     * Sets template
     * @param string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        if ($url = $this->getTemplate()) {
            $this->setTemplate($transformer->process($url));
        }
    }
}
