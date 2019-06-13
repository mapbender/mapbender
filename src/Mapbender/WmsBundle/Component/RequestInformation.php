<?php

namespace Mapbender\WmsBundle\Component;

/**
 * @author Paul Schmidt
 */
class RequestInformation
{

    /** @var string|null */
    public $httpGet;
    /** @var string|null */
    public $httpPost;
    /** @var string[] */
    public $formats = array();

    /**
     * Get httpGet
     * 
     * @return string|null
     */
    public function getHttpGet()
    {
        return $this->httpGet;
    }

    /**
     * Set httpGet
     * @param string $value
     * @return $this
     */
    public function setHttpGet($value)
    {
        $this->httpGet = $value;
        return $this;
    }

    /**
     * Get httpPost
     * 
     * @return string|null
     */
    public function getHttpPost()
    {
        return $this->httpPost;
    }

    /**
     * Set httpPost
     * @param string $value
     * @return $this
     */
    public function setHttpPost($value)
    {
        $this->httpPost = $value;
        return $this;
    }

    /**
     * Get formats
     * 
     * @return string[]
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * Set formats
     * @param string[] $value
     * @return $this
     */
    public function setFormats($value)
    {
        $this->formats = $value;
        return $this;
    }

    /**
     * Add format
     * @param string $value
     * @return $this
     */
    public function addFormat($value)
    {
        $this->formats[] = $value;
        return $this;
    }

}
