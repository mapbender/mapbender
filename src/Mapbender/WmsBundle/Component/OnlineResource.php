<?php
namespace Mapbender\WmsBundle\Component;

/**
 * OnlineResource class.
 *
 * @author Paul Schmidt
 */
class OnlineResource
{
    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $format;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $href;

    /**
     *
     * @param string $format
     * @param string $href
     */
    public function __construct($format = null, $href = null)
    {
        $this->format = $format;
        $this->href   = $href;
    }

    /**
     * Set format
     *
     * @param string $format
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set href
     *
     * @param string $href
     * @return $this
     */
    public function setHref($href)
    {
        $this->href = $href;

        return $this;
    }

    /**
     * Get href
     *
     * @return string
     */
    public function getHref()
    {
        return $this->href;
    }

}
