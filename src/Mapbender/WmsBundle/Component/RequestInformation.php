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
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $httpGet;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $httpPost;

    /**
     * ORM\Column(type="array", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $formats;

    /**
     * Creates a RequestInformation object from parameters
     * @param array $parameters
     */
    public static function create(array $parameters)
    {
        if(is_array($parameters))
        {
            $rqi = new RequestInformation();
            if(isset($parameters["httpPost"]))
            {
                $rqi->setHttpPost($parameters["httpPost"]);
            }
            if(isset($parameters["httpGet"]))
            {
                $rqi->setHttpGet($parameters["httpGet"]);
            }
            if(isset($parameters["formats"]))
            {
                $rqi->setFormats($parameters["formats"]);
            }
            if($this->getHttpGet() || $this->getHttpPost())
            {
                return $rqi;
            }
        }
        return null;
    }

    public function __construct($httpGet = null, $httpPost = null,
            $formats = array())
    {
        $this->httpGet = $httpGet;
        $this->httpPost = $httpPost;
        $this->formats = $formats;
    }

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
     * Get object as array
     * 
     * @return array
     */
    public function toArray()
    {
        return array(
            "httpGet" => $this->httpGet,
            "httpPost" => $this->httpPost,
            "formats" => $this->formats
        );
    }

}