<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\InstanceConfigurationOptions;

/**
 * Description of WmtsInstanceConfiguration
 *
 * @author Paul Schmidt
 */
class WmtsInstanceConfigurationOptions extends InstanceConfigurationOptions
{
//    /**
//     * ORM\Column(type="string", nullable=true)
//     */
//    protected $vendor;

//
//    /**
//     * ORM\Column(type="array", nullable=true)
//     */
//
//    public $bbox;
//
//    /**
//     * Sets a bbox
//     *
//     * @param array $bbox source bbox
//     * @return WmtsInstanceConfiguration
//     */
//    public function setBbox($bbox)
//    {
//        $this->bbox = $bbox;
//        return $this;
//    }
//
//    /**
//     * Returns a bbox
//     *
//     * @return array bbox
//     */
//    public function getBbox()
//    {
//        return $this->bbox;
//    }
//
//    public function setVendor($val)
//    {
//        $this->vendor = $val;
//    }
//
//    public function getVendor()
//    {
//        return $this->vendor;
//    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return array(
//            "url" => $this->url,
            "proxy" => $this->proxy,
            "visible" => $this->visible,
//            "format" => $this->format,
//            "info_format" => $this->infoformat,
//            "transparent" => $this->transparency,
            "opacity" => $this->opacity,
//            "bbox" => $this->bbox,
//            "vendor" => $this->vendor
        );
    }

    /**
     * @inheritdoc
     */
    public static function fromArray($options, $strict = true)
    {
        $ico = null;
        if ($options && is_array($options)) {
            $ico = new WmtsInstanceConfigurationOptions();
            if (isset($options["url"])) {
                $ico->url = $options["url"];
            }
            if (isset($options["proxy"])) {
                $ico->proxy = $options["proxy"];
            }
            if (isset($options["visible"])) {
                $ico->visible = $options["visible"];
            }
//            if (isset($options["format"])) {
//                $ico->format = $options["format"];
//            }
//            if (isset($options["info_format"])) {
//                $ico->infoformat = $options["info_format"];
//            }
//            if (isset($options["transparent"])) {
//                $ico->transparency = $options["transparent"];
//            }
            if (isset($options["opacity"])) {
                $ico->opacity = $options["opacity"];
            }
//            if (isset($options["bbox"])) {
//                $ico->bbox = $options["bbox"];
//            }
//            if (isset($options["vendor"])) {
//                $ico->vendor = $options["vendor"];
//            }
        }
        return $ico;
    }
}
