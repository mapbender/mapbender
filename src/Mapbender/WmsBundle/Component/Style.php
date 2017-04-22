<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Style class.
 * @author Paul Schmidt
 */
class Style
{

    /**
     * ORM\Column(type="string", nullable=true)
     */
    protected $name = "";

    /**
     * ORM\Column(type="string", nullable=true)
     */
    protected $title = "";

    /**
     * ORM\Column(type="string", nullable=true)
     */
    protected $abstract = "";

    /**
     * ORM\Column(type="object", nullable=true)
     */
    protected $legendUrl;

    /**
     * ORM\Column(type="object", nullable=true)
     */
    protected $styleSheetUrl;

    /**
     * ORM\Column(type="object", nullable=true)
     */
    protected $styleUlr;

    /**
     * Set name
     *
     * @param string $name
     * @return Style
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
     * @return Style
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
     * @return Style
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
     * @param LegendUrl $legendUrl
     * @return Style
     */
    public function setLegendUrl(LegendUrl $legendUrl)
    {
        $this->legendUrl = $legendUrl;

        return $this;
    }

    /**
     * Get legendUrl
     *
     * @return \stdClass 
     */
    public function getLegendUrl()
    {
        return $this->legendUrl;
    }

    /**
     * Set styleSheetUrl
     *
     * @param OnlineResource $styleSheetUrl
     * @return Style
     */
    public function setStyleSheetUrl(OnlineResource $styleSheetUrl = NULL)
    {
        $this->styleSheetUrl = $styleSheetUrl;

        return $this;
    }

    /**
     * Get styleSheetUrl
     *
     * @return \stdClass 
     */
    public function getStyleSheetUrl()
    {
        return $this->styleSheetUrl;
    }

    /**
     * Set styleUlr
     *
     * @param OnlineResource $styleUlr
     * @return Style
     */
    public function setStyleUlr(OnlineResource $styleUlr = NULL)
    {
        $this->styleUlr = $styleUlr;

        return $this;
    }

    /**
     * Get styleUlr
     *
     * @return \stdClass 
     */
    public function getStyleUlr()
    {
        return $this->styleUlr;
    }

}
