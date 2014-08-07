<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Signer;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SourceConfigurationOptions
 *
 * @author Paul Schmidt
 */
abstract class InstanceConfigurationOptions
{
    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $url;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $format;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $infoformat;

    /**
     * ORM\Column(type="float", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $opacity;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $proxy;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $visible;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $transparency;

    /**
     * Sets an url
     * 
     * @param string $url source url
     * @return SierviceConfigurationOptions 
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Returns a source url
     * 
     * @return string url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets a format
     * 
     * @param string $format source format
     * @return SierviceConfigurationOptions 
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Returns a format
     * 
     * @return string format
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Sets a infoformat
     * 
     * @param string $infoformat source infoformat
     * @return SierviceConfigurationOptions 
     */
    public function setInfoformat($infoformat)
    {
        $this->infoformat = $infoformat;
        return $this;
    }

    /**
     * Returns an infoformat
     * 
     * @return string infoformat
     */
    public function getInfoformat()
    {
        return $this->infoformat;
    }

    /**
     * Sets an opacity
     * 
     * @param float $opacity source opacity
     * @return SierviceConfigurationOptions 
     */
    public function setOpacity($opacity)
    {
        $this->opacity = $opacity;
        return $this;
    }

    /**
     * Returns an opacity
     * 
     * @return float opacity
     */
    public function getOpacity()
    {
        return $this->opacity;
    }

    /**
     * Sets a proxy
     * 
     * @param boolean $proxy source proxy
     * @return SierviceConfigurationOptions 
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * Returns a proxy
     * 
     * @return boolean proxy
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Sets a visible
     * 
     * @param boolean $visible source visibility
     * @return SierviceConfigurationOptions 
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * Returns a visible
     * 
     * @return boolean visible
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Sets a transparency
     * 
     * @param boolean $transparency source transparency
     * @return SierviceConfigurationOptions 
     */
    public function setTransparency($transparency)
    {
        $this->transparency = $transparency;
        return $this;
    }

    /**
     * Returns a transparency
     * 
     * @return boolean transparency
     */
    public function getTransparency()
    {
        return $this->transparency;
    }

    /**
     * Sings a url
     * 
     * @return boolean transparency
     */
    public function signUrl(Signer $signer = null)
    {
        if ($signer) {
            $this->url = $signer->signUrl($this->url);
        }
    }

    /**
     * Returns InstanceConfigurationOptions as array
     * 
     * @return array
     */
    public abstract function toArray();

    /**
     * Creates an InstanceConfigurationOptions from options
     * 
     * @param array $options array with options
     * @return InstanceConfigurationOptions
     */
    public static function fromArray($options)
    {
        
    }

}

