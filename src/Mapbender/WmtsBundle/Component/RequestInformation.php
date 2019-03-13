<?php

namespace Mapbender\WmtsBundle\Component;

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
    public $httpGetRestful;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $httpGetKvp;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $httpPost;

    /**
     * Get httpGet
     *
     * @return string
     */
    public function getHttpGetRestful()
    {
        return $this->httpGetRestful;
    }

    /**
     * Set httpGetRestful
     *
     * @param string $value
     * @return $this
     */
    public function setHttpGetRestful($value)
    {
        $this->httpGetRestful = $value;
        return $this;
    }

    /**
     * Get httpGetKvp
     *
     * @return string
     */
    public function getHttpGetKvp()
    {
        return $this->httpGetKvp;
    }

    /**
     * Set httpGetKvp
     *
     * @param string $value
     * @return $this
     */
    public function setHttpGetKvp($value)
    {
        $this->httpGetKvp = $value;
        return $this;
    }

    /**
     * Get httpPost
     * @return string
     */
    public function getHttpPost()
    {
        return $this->httpPost;
    }

    /**
     * Set httpPost
     *
     * @param string $value
     * @return $this
     */
    public function setHttpPost($value)
    {
        $this->httpPost = $value;
        return $this;
    }
}
