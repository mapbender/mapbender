<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;

/**
 * @author Paul Schmidt
 */
class Style implements MutableUrlTarget
{
    /** @var string */
    public $name = "";

    /** @var string */
    public $title = "";

    /** @var string */
    public $abstract = "";

    /** @var LegendUrl|null */
    public $legendUrl;

    public function __unserialize(array $array)
    {
        foreach (['name', 'title', 'abstract', 'legendUrl'] as $key) {
            if (array_key_exists($key, $array)) $this->$key = $array[$key];
        }
    }

    /**
     * Set name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set abstract
     *
     * @param string $abstract
     * @return $this
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     * Get abstract
     *
     * @return string
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * Set legendUrl
     *
     * @param LegendUrl|null $legendUrl
     * @return $this
     */
    public function setLegendUrl(LegendUrl $legendUrl = null)
    {
        $this->legendUrl = $legendUrl;
        return $this;
    }

    /**
     * Get legendUrl
     *
     * @return LegendUrl|null
     */
    public function getLegendUrl()
    {
        return $this->legendUrl;
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        $legendUrl = $this->getLegendUrl();
        if ($legendUrl && ($onlineResource = $legendUrl->getOnlineResource())) {
            $onlineResource->mutateUrls($transformer);
            $this->setLegendUrl(clone $legendUrl);
            $this->getLegendUrl()->setOnlineResource($onlineResource);
        }
    }
}
