<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;

/**
 * @author Paul Schmidt
 */
class RequestInformation implements MutableUrlTarget
{

    /** @var string|null */
    public $httpGetRestful;

    /** @var string|null */
    public $httpGetKvp;

    /** @var string|null */
    public $httpPost;

    /**
     * @return string|null
     */
    public function getHttpGetRestful()
    {
        return $this->httpGetRestful;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setHttpGetRestful($value)
    {
        $this->httpGetRestful = $value;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHttpGetKvp()
    {
        return $this->httpGetKvp;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setHttpGetKvp($value)
    {
        $this->httpGetKvp = $value;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHttpPost()
    {
        return $this->httpPost;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setHttpPost($value)
    {
        $this->httpPost = $value;
        return $this;
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        if ($url = $this->getHttpGetKvp()) {
            $this->setHttpGetKvp($transformer->process($url));
        }
        if ($url = $this->getHttpGetRestful()) {
            $this->setHttpGetRestful($transformer->process($url));
        }
        if ($url = $this->getHttpPost()) {
            $this->setHttpPost($transformer->process($url));
        }
    }
}
