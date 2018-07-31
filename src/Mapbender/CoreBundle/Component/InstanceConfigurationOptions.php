<?php
namespace Mapbender\CoreBundle\Component;
use Mapbender\CoreBundle\Component\Base\ConfigurationBase;

/**
 * Description of SourceConfigurationOptions
 *
 * @author Paul Schmidt
 */
abstract class InstanceConfigurationOptions extends ConfigurationBase
{
    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $url;

    /**
     * ORM\Column(type="float", nullable=true)
     */
    public $opacity = 1;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    public $proxy = false;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    public $visible = true;

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
     * @return $this
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
     *
     * @deprecated this should be a getter, not a mutator, if it should exist at all here. URL signing is presentation
     * layer.
     * @internal
     * @todo: find callers
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
    public function toArray()
    {
        return array(
            "url" => $this->url,
            "opacity" => $this->opacity,
            "proxy" => $this->proxy,
            "visible" => $this->visible,
        );
    }
}
