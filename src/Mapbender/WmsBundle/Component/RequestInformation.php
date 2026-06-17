<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;

/**
 * @author Paul Schmidt
 */
class RequestInformation implements MutableUrlTarget
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

    public function mutateUrls(OneWayTransformer $transformer)
    {
        if ($this->httpGet) {
            $this->setHttpGet($transformer->process($this->httpGet));
        }
        if ($this->httpPost) {
            $this->setHttpPost($transformer->process($this->httpPost));
        }
    }

    /**
     * Reconstruct a RequestInformation object from a plain array (e.g. after JSON column hydration by Doctrine/DBAL 4).
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        $instance = new static();
        $instance->httpGet  = $data['httpGet']  ?? null;
        $instance->httpPost = $data['httpPost'] ?? null;
        $instance->formats  = $data['formats']  ?? [];
        return $instance;
    }
}
