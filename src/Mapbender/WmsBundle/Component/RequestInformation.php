<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Utils\UrlUtil;

/**
 * RequestInformation class.
 *
 * @author Paul Schmidt
 */
class RequestInformation
{

    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $httpGet;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $httpPost;

    /**
     * ORM\Column(type="array", nullable=true)
     */
    public $formats = array();

    /**
     * Get httpGet
     * 
     * @return string
     */
    public function getHttpGet()
    {
        return $this->httpGet;
    }

    /**
     * Set httpGet
     * @param string $value 
     */
    public function setHttpGet($value)
    {
        $this->httpGet = $value;
        return $this;
    }

    /**
     * Get httpPost
     * 
     * @return string
     */
    public function getHttpPost()
    {
        return $this->httpPost;
    }

    /**
     * Set httpPost
     * @param string $value 
     */
    public function setHttpPost($value)
    {
        $this->httpPost = $value;
        return $this;
    }

    /**
     * Get formats
     * 
     * @return array
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * Set formats
     * @param array $value 
     */
    public function setFormats($value)
    {
        $this->formats = $value;
        return $this;
    }

    /**
     * Add format
     * @param string $value 
     */
    public function addFormat($value)
    {
        $this->formats[] = $value;
        return $this;
    }

    /**
     * @param string $to new host name
     * @param string|null $from old host name (optional); if given, only replace if hostname in $url equals $from
     * @return $this
     */
    public function replaceHost($to, $from = null)
    {
        if ($this->httpGet) {
            $this->httpGet  = UrlUtil::replaceHost($this->httpGet, $to, $from);
        }
        if ($this->httpPost) {
            $this->httpPost = UrlUtil::replaceHost($this->httpPost, $to, $from);
        }
        return $this;
    }
}
