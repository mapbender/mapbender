<?php

namespace Mapbender\WmtsBundle\Entity;

/**
 * @author Paul Schmidt
 */
class LegendUrl
{
    /**
     * A legend format
     * @var string
     */
    public $format;

    /**
     * A legend href
     * @var string
     */
    public $href;

    public function getFormat()
    {
        return $this->format;
    }

    public function getHref()
    {
        return $this->href;
    }

    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }
}
