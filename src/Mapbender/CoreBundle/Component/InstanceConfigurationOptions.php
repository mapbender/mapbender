<?php
namespace Mapbender\CoreBundle\Component;

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
    public $url;

    /**
     * ORM\Column(type="float", nullable=true)
     */
    public $opacity;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    public $proxy;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    public $visible;

    /**
     * Sets an url
     * @param string $url source url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Returns a source url
     * @return string url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets an opacity
     * @param float $opacity source opacity
     * @return $this
     */
    public function setOpacity($opacity)
    {
        $this->opacity = $opacity;
        return $this;
    }

    /**
     * Returns an opacity
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
     * @return InstanceConfigurationOptions
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * Returns a proxy
     * @return boolean proxy
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Sets a visible
     * @param boolean $visible source visibility
     * @return $this
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * Returns a visible
     * @return boolean visible
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Sings a url
     *
     * @param Signer $signer
     * @return bool transparency
     */
    public function signUrl(Signer $signer = null)
    {
        if ($signer) {
            $this->url = $signer->signUrl($this->url);
        }
    }

    /**
     * Returns InstanceConfigurationOptions as array
     * @return array
     */
    abstract public function toArray();

    /**
     * Creates an InstanceConfigurationOptions from options
     * @param array $options array with options
     * @return InstanceConfigurationOptions
     */
    public static function fromArray($options)
    {
        
    }
}
