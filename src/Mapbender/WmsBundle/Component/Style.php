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
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $name = "";

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $title = "";

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $abstract = "";

    /**
     * ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $legendUrl;

    /**
     * ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $styleSheetUrl;

    /**
     * ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $styleUlr;

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

    public function replaceHost($to, $from)
    {
        /**
         * @todo: upstream functions getLegendUrl, getStyleUlr and getStyleSheetUrl
         *    are marked as returning stdClass instances, but this seems to be an
         *    error in the annotions. All setters enforce strict types, and all known
         *    direct modifications (these are public attribs!) set instances of these
         *    same types.
         *
         *    Return type annotations should be updated if possible.
         */
        $legendUrl = $this->getLegendUrl();
        $styleUrl = $this->getStyleUlr();
        $styleSheetUrl = $this->getStyleSheetUrl();
        /** @var LegendUrl $legendUrl */
        /** @var OnlineResource $styleSheetUrl */
        /** @var OnlineResource $styleUrl */
        if ($legendUrl && $legendUrl->getOnlineResource()) {
            $legendUrl->getOnlineResource()->replaceHost($to, $from);
            $legendUrl->setOnlineResource($legendUrl->getOnlineResource());
        }
        if ($styleUrl) {
            $styleUrl->replaceHost($to, $from);
        }
        if ($styleSheetUrl) {
            $styleSheetUrl->replaceHost($to, $from);
        }
        $this->setLegendUrl($this->getLegendUrl());
        $this->setStyleUlr($this->getStyleUlr());
        $this->setStyleSheetUrl($this->getStyleSheetUrl());
    }
}
