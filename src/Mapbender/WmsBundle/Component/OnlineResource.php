<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Utils\UrlUtil;

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

    /**
     * Create online resource
     * #
     *
     * @param null $format
     * @param null $href
     * @return OnlineResource|null
     */
    public static function create($format = null, $href = null)
    {
        if ($href === null) {
            $olr = null;
        } else {
            $olr = new OnlineResource();
            $olr->setFormat($format);
            $olr->setHref($href);
        }
        return $olr;
    }

    /**
     * Update host in $this->href
     *
     * @param string $to new host name
     * @param string|null $from old host name (optional); if given, only replace if hostname in $this->href equals $from
     */
    public function replaceHost($to, $from = null)
    {
        $this->href = UrlUtil::replaceHost($this->href, $to, $from);
    }
}
