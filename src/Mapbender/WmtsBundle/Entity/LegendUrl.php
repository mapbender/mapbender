<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Entity;

/**
 * Description of LegendUrl
 *
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
