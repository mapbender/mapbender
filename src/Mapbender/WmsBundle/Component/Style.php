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

    /** @var OnlineResource|null */
    public $styleSheetUrl;

    /** @var OnlineResource|null */
    public $styleUlr;

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

    /**
     * Set styleSheetUrl
     *
     * @param OnlineResource|null $styleSheetUrl
     * @return $this
     */
    public function setStyleSheetUrl(OnlineResource $styleSheetUrl = NULL)
    {
        $this->styleSheetUrl = $styleSheetUrl;
        return $this;
    }

    /**
     * Get styleSheetUrl
     *
     * @return OnlineResource
     */
    public function getStyleSheetUrl()
    {
        return $this->styleSheetUrl;
    }

    /**
     * Set styleUlr
     *
     * @param OnlineResource|null $styleUlr
     * @return $this
     */
    public function setStyleUlr(OnlineResource $styleUlr = NULL)
    {
        $this->styleUlr = $styleUlr;
        return $this;
    }

    /**
     * @return OnlineResource|null
     */
    public function getStyleUlr()
    {
        return $this->styleUlr;
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        $legendUrl = $this->getLegendUrl();
        if ($legendUrl && ($onlineResource = $legendUrl->getOnlineResource())) {
            $onlineResource->mutateUrls($transformer);
            $this->setLegendUrl(clone $legendUrl);
            $this->getLegendUrl()->setOnlineResource($onlineResource);
        }
        if ($onlineResource = $this->getStyleUlr()) {
            $onlineResource->mutateUrls($transformer);
            $this->setStyleUlr(clone $onlineResource);
        }
        if ($onlineResource = $this->getStyleSheetUrl()) {
            $onlineResource->mutateUrls($transformer);
            $this->setStyleSheetUrl(clone $onlineResource);
        }
    }
}
