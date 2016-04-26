<?php

namespace Mapbender\WmsBundle\Component;

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

}
