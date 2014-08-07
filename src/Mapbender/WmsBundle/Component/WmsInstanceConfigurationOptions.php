<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\InstanceConfigurationOptions;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WmsInstanceConfiguration
 *
 * @author Paul Schmidt
 */
class WmsInstanceConfigurationOptions extends InstanceConfigurationOptions
{

    /**
     * ORM\Column(type="string", nullable=true)
     */
    protected $vendor;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $tiled;

    /**
     * ORM\Column(type="array", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $bbox;

    /**
     * Sets a tiled
     *
     * @param boolean $tiled source tiled
     * @return WmsInstanceConfiguration
     */
    public function setTiled($tiled)
    {
        $this->tiled = $tiled;
        return $this;
    }

    /**
     * Returns a tiled
     *
     * @return boolean tiled
     */
    public function getTiled()
    {
        return $this->tiled;
    }

    /**
     * Sets a bbox
     *
     * @param array $bbox source bbox
     * @return WmsInstanceConfiguration
     */
    public function setBbox($bbox)
    {
        $this->bbox = $bbox;
        return $this;
    }

    /**
     * Returns a bbox
     *
     * @return array bbox
     */
    public function getBbox()
    {
        return $this->bbox;
    }

    public function setVendor($val)
    {
        $this->vendor = $val;
    }

    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return array(
            "url" => $this->url,
            "proxy" => $this->proxy,
            "visible" => $this->visible,
            "format" => $this->format,
            "info_format" => $this->infoformat,
            "transparent" => $this->transparency,
            "opacity" => $this->opacity,
            "tiled" => $this->tiled,
            "bbox" => $this->bbox,
            "vendor" => $this->vendor
        );
    }

    /**
     * @inheritdoc
     */
    public static function fromArray($options)
    {
        $ico = null;
        if ($options && is_array($options)) {
            $ico = new WmsInstanceConfigurationOptions();
            if (isset($options["url"])) {
                $ico->url = $options["url"];
            }
            if (isset($options["proxy"])) {
                $ico->proxy = $options["proxy"];
            }
            if (isset($options["visible"])) {
                $ico->visible = $options["visible"];
            }
            if (isset($options["format"])) {
                $ico->format = $options["format"];
            }
            if (isset($options["info_format"])) {
                $ico->infoformat = $options["info_format"];
            }
            if (isset($options["transparent"])) {
                $ico->transparency = $options["transparent"];
            }
            if (isset($options["opacity"])) {
                $ico->opacity = $options["opacity"];
            }
            if (isset($options["tiled"])) {
                $ico->tiled = $options["tiled"];
            }
            if (isset($options["bbox"])) {
                $ico->bbox = $options["bbox"];
            }
            if (isset($options["vendor"])) {
                $ico->vendor = $options["vendor"];
            }
        }
        return $ico;
    }

}
