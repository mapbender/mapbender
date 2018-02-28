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
     * @deprecated this should be a getter, not a mutator
     * @internal
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

    /**
     * @return array
     * Policy; no "get" prefix for non-serializible getter functions in objects that are persisted or exported
     */
    public static function defaults()
    {
        // all properties uninitialized
        return array(
            "url" => null,
            "opacity" => null,
            "proxy" => null,
            "visible" => null,
        );
    }

    /**
     * Creates an InstanceConfigurationOptions from options
     * @param array $options array with options
     * @param bool $strict to throw if unknown options have been passed
     * @return static
     */
    public static function fromArray($options, $strict = true)
    {
        if (!is_array($options)) {
            if ($strict) {
                throw new \InvalidArgumentException('Options must be an array, is: ' . print_r($options, true));
            } else {
                $options = array();
            }
        }
        $instance = new static();
        $instance->populateAttributes($options, static::defaults(), $strict);
        return $instance;
    }

    protected function populateAttributes($options, $defaults, $strict)
    {
        $mergedOptions = array_replace($defaults, $options);
        if ($strict) {
            $validateAgainst = $defaults ?: $this->defaults();
            $badKeys = array_keys(array_diff_key($validateAgainst, $options));
            if ($badKeys) {
                throw new \RuntimeException("Unsupported keys in options: " . implode(", ", $badKeys));
            }
        }
        foreach ($mergedOptions as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
