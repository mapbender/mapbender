<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\InstanceConfigurationOptions;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Entity\WmsInstance;

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
    public $version;

    /**
     *
     * ORM\Column(type="string", nullable=true)
     */
    public $exceptionformat;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $format;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $infoformat;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    public $transparency;

    /**
     * ORM\Column(type="array", nullable=true)
     */
    protected $vendorspecifics;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $tiled;

    /**
     * ORM\Column(type="array", nullable=true)
     */
    public $bbox;
    
    /**
     * ORM\Column(type="array", nullable=true)
     */
    public $dimensions;

    /**
     * ORM\Column(type="integer", options={"default" = 0})
     */
    public $buffer = 0;

    /**
     * ORM\Column(type="decimal", scale=2, options={"default" = 1.25})
     */
    public $ratio = 1.25;
    
    /**
     * Returns a version
     * @return string version
     */
    function getVersion()
    {
        return $this->version;
    }

    /**
     * Sets a wms version
     *
     * @param string $version version
     * @return $this
     */
    function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }


    /**
     * @return string
     */
    function getExceptionformat()
    {
        return $this->exceptionformat;
    }

    /**
     * @param string $exceptionformat
     * @return $this
     */
    function setExceptionformat($exceptionformat)
    {
        $this->exceptionformat = $exceptionformat;
        return $this;
    }



    /**
     * Sets a format
     *
     * @param string $format source format
     * @return $this
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
     * Sets a info format
     *
     * @param string $infoFormat source infoformat
     * @return $this
     */
    public function setInfoformat($infoFormat)
    {
        $this->infoformat = $infoFormat;
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
     * Sets a transparency
     *
     * @param boolean $transparency source transparency
     * @return $this
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
     * Sets a tiled
     *
     * @param boolean $tiled source tiled
     * @return $this
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
     * @return $this
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

    /**
     * @param array $vendorspecifics
     * @return $this
     */
    public function setVendorspecifics(array $vendorspecifics)
    {
        $this->vendorspecifics = $vendorspecifics;
        return $this;
    }

    /**
     * @return array
     */
    public function getVendorspecifics()
    {
        return $this->vendorspecifics;
    }

    /**
     * @return mixed
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * @param array $dimensions
     * @return $this
     */
    public function setDimensions(array $dimensions)
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    /**
     * @param $buffer
     * @return $this
     */
    public function setBuffer($buffer)
    {
        $this->buffer = intval($buffer);
        return $this;
    }

    /**
     * @return int
     */
    public function getBuffer()
    {
        if (null != $this->buffer) {
            return $this->buffer;
        } else {
            return 1;
        }
    }

    /**
     * @return float
     */
    public function getRatio()
    {
        return $this->ratio;
    }

    /**
     * @param float $ratio
     * @return $this
     */
    public function setRatio($ratio)
    {
        $this->ratio = floatval($ratio);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return array(
            "version" => $this->version,
            "url" => $this->url,
            "proxy" => $this->proxy,
            "visible" => $this->visible,
            "format" => $this->format,
            "info_format" => $this->infoformat,
            "exception_format" => $this->exceptionformat,
            "transparent" => $this->transparency,
            "opacity" => $this->opacity,
            "tiled" => $this->tiled,
            "bbox" => $this->bbox,
            "vendorspecifics" => $this->vendorspecifics,
            "dimensions" => $this->dimensions,
            "buffer" => $this->buffer,
            "ratio" => $this->ratio,
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
            if (isset($options["vendorspecifics"])) {
                $ico->vendorspecifics = $options["vendorspecifics"];
            }
            if (isset($options["buffer"])) {
                $ico->buffer = $options["buffer"];
            }
            if (isset($options["ratio"])) {
                $ico->ratio = $options["ratio"];
            }
            if (isset($options["version"])) {
                $ico->version = $options["version"];
            }
            if (isset($options["exception_format"])) {
                $ico->exceptionformat = $options["exception_format"];
            }
        }
        return $ico;
    }

    public static function fromEntity(WmsInstance $instance)
    {
        $ico = new static();
        $source = $instance->getSource();

        $ico->setUrl($source->getGetMap()->getHttpGet());
        $dimensions = array();
        foreach ($instance->getDimensions() as $dimension) {
            if ($dimension->getActive()) {
                $dimensions[] = $dimension->getConfiguration();
                if ($dimension->getDefault()) {
                    $help = array($dimension->getParameterName() => $dimension->getDefault());
                    $ico->setUrl(UrlUtil::validateUrl($ico->getUrl(), $help, array()));
                }
            }
        }
        $ico->setDimensions($dimensions);
        $vendorsecifics = array();
        foreach ($instance->getVendorspecifics() as $key => $vendorspec) {
            $handler = new VendorSpecificHandler($vendorspec);
            /* add to url only simple vendor specific with valid default value */
            if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE && $handler->isVendorSpecificValueValid()) {
                $vendorsecifics[] = $handler->getConfiguration();
                $help             = $handler->getKvpConfiguration(null);
                $ico->setUrl(UrlUtil::validateUrl($ico->getUrl(), $help, array()));
            }
        }
        $ico->setVendorspecifics($vendorsecifics);

        $ico->setProxy($instance->getProxy())
            ->setVisible($instance->getVisible())
            ->setFormat($instance->getFormat())
            ->setInfoformat($instance->getInfoformat())
            ->setTransparency($instance->getTransparency())
            ->setOpacity($instance->getOpacity() / 100)
            ->setTiled($instance->getTiled())
            ->setBuffer($instance->getBuffer())
            ->setRatio($instance->getRatio())
            ->setVersion($instance->getSource()->getVersion())
            ->setExceptionformat($instance->getExceptionformat());

        $rootLayer = $instance->getRootlayer();
        $boundingBoxMap = array();
        foreach ($rootLayer->getSourceItem()->getMergedBoundingBoxes() as $bbox) {
            $boundingBoxMap[$bbox->getSrs()] = $bbox->toCoordsArray();
        }
        $ico->setBbox($boundingBoxMap);

        return $ico;
    }
}
