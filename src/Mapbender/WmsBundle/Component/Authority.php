<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;

/**
 * @author Paul Schmidt
 */
class Authority implements MutableUrlTarget
{

    /** @var string|null */
    public $url;

    /** @var string|null */
    public $name;

    /**
     * Get url
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set url
     * @param string $value
     * @return $this
     */
    public function setUrl($value)
    {
        $this->url = $value;
        return $this;
    }

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     * @param string $value
     * @return $this
     */
    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * @return string[]
     */
    public function toArray()
    {
        return array(
            'url' => $this->url,
            'name' => $this->name,
        );
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        if ($this->getUrl()) {
            $this->setUrl($transformer->process($this->getUrl()));
        }
    }
}
