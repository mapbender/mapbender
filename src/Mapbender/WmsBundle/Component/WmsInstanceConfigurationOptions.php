<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\InstanceConfigurationOptions;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmcBundle\Component\WmcParser110;
use Mapbender\WmsBundle\Entity\WmsInstance;

/**
 * Description of WmsInstanceConfiguration
 *
 * @author Paul Schmidt
 *
 * @deprecated this entire class is only used transiently to capture values via its setters, then converted to
 *     array and discared. The sanitization performed along the way is minimal.
 *     The only remaining use is in WmcParser110.
 *
 * @see WmcParser110::parseLayer()
 * @see WmsInstanceConfiguration::fromEntity()
 * @internal
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
        return parent::toArray() + array(
            "version" => $this->version,
            "format" => $this->format,
            "info_format" => $this->infoformat,
            "exception_format" => $this->exceptionformat,
            "transparent" => $this->transparency,
            "tiled" => $this->tiled,
            "bbox" => $this->bbox,
            "vendorspecifics" => $this->vendorspecifics,
            "dimensions" => $this->dimensions,
            "buffer" => $this->buffer,
            "ratio" => $this->ratio,
        );
    }

    public static function fromEntity(WmsInstance $instance)
    {
        $source = $instance->getSource();

        $effectiveUrl = $source->getGetMap()->getHttpGet();

        $dimensions = array();
        foreach ($instance->getDimensions() as $dimension) {
            if ($dimension->getActive()) {
                $dimensions[] = $dimension->getConfiguration();
                if ($dimension->getDefault()) {
                    $help = array($dimension->getParameterName() => $dimension->getDefault());
                    $effectiveUrl = UrlUtil::validateUrl($effectiveUrl, $help, array());
                }
            }
        }
        $vendorSpecific = array();
        foreach ($instance->getVendorspecifics() as $key => $vendorspec) {
            $handler = new VendorSpecificHandler($vendorspec);
            /* add to url only simple vendor specific with valid default value */
            if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE && $handler->isVendorSpecificValueValid()) {
                $vendorSpecific[] = $handler->getConfiguration();
                $help             = $handler->getKvpConfiguration(null);
                $effectiveUrl = UrlUtil::validateUrl($effectiveUrl, $help, array());
            }
        }
        $rootLayer = $instance->getRootlayer();
        $boundingBoxMap = array();
        foreach ($rootLayer->getSourceItem()->getMergedBoundingBoxes() as $bbox) {
            $boundingBoxMap[$bbox->getSrs()] = $bbox->toCoordsArray();
        }
        $ratio = $instance->getRatio();
        if ($ratio !== null) {
            $ratio = floatval($ratio);
        }
        return static::fromArray(array(
            'url' => $effectiveUrl,
            'dimensions' => $dimensions,
            'vendorspecifics' => $vendorSpecific,
            'proxy' => $instance->getProxy(),
            'visible' => $instance->getVisible(),
            'format' => $instance->getFormat(),
            'info_format' => $instance->getInfoformat(),
            'transparent' => $instance->getTransparency(),
            'opacity' => ($instance->getOpacity() / 100),
            'tiled' => $instance->getTiled(),
            'buffer' => $instance->getBuffer(),
            'ratio' => $ratio,
            'version' => $instance->getSource()->getVersion(),
            'exception_format' => $instance->getExceptionformat(),
            'bbox' => $boundingBoxMap,
        ));
    }

    protected static function keyToAttributeMapping()
    {
        // remap our three "danger zone" keys
        return array(
            'exception_format' => 'exceptionformat',
            'info_format' => 'infoformat',
            'transparent' => 'transparency',
        );
    }
}
