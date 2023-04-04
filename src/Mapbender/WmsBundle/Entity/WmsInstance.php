<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Component\Presenter\WmsSourceService;
use Mapbender\WmsBundle\Component\VendorSpecific;
use Mapbender\WmsBundle\Component\Wms\SourceInstanceFactory;
use Mapbender\WmsBundle\Component\WmsMetadata;

/**
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_wms_wmsinstance")
 */
class WmsInstance extends SourceInstance
{
    /**
     * @ORM\ManyToOne(targetEntity="Mapbender\WmsBundle\Entity\WmsSource", inversedBy="instances", cascade={"refresh"})
     * @ORM\JoinColumn(name="wmssource", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $source;

    /**
     * @var WmsInstanceLayer[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="WmsInstanceLayer", mappedBy="sourceInstance", cascade={"persist", "remove", "refresh"})
     * @ORM\JoinColumn(name="layers", referencedColumnName="id")
     * @ORM\OrderBy({"priority" = "asc", "id" = "asc"})
     */
    protected $layers;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $srs;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $format;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $infoformat;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $exceptionformat = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $transparency = true;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $opacity = 100;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $proxy = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $tiled = false;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $dimensions;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $vendorspecifics;

    /**
     * @ORM\Column(type="integer", options={"default" = 0})
     */
    protected $buffer = 0;

    /**
     * @ORM\Column(type="decimal", scale=2, options={"default" = 1.25})
     */
    protected $ratio = 1.25;

    const LAYER_ORDER_TOP_DOWN  = 'standard';
    const LAYER_ORDER_BOTTOM_UP = 'reverse';

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $layerOrder;

    /**
     * WmsInstance constructor.
     */
    public function __construct()
    {
        $this->layers     = new ArrayCollection();
        $this->dimensions = array();
        $this->vendorspecifics = array();
    }

    private function __getLayersRecursive(WmsInstanceLayer $layer)
    {
        /** @var WmsInstanceLayer $layer */
        $sublayers = $layer->getSublayer()->getValues();
        $sublayerlists = array_merge(array($sublayers), array_map(array($this, '__getLayersRecursive'), $sublayers));
        return \call_user_func_array('\array_merge', $sublayerlists);
    }

    public function __clone()
    {
        if ($this->id) {
            $this->setId(null);
            $rootLayer = clone $this->getRootlayer();
            $rootLayer->setSourceInstance($this);
            $clonedLayers = array($rootLayer);
            foreach ($this->__getLayersRecursive($rootLayer) as $extraLayer) {
                /** @var WmsInstanceLayer $extraLayer */
                $extraLayer->setSourceInstance($this);
                $clonedLayers[] = $extraLayer;
            }
            $this->setLayers(new ArrayCollection($clonedLayers));
        }
    }

    /**
     * Returns dimensions
     *
     * @return DimensionInst[]
     */
    public function getDimensions()
    {
        return $this->dimensions ? : array();
    }

    /**
     * Sets dimensions
     *
     * @param DimensionInst[] $dimensions
     * @return $this
     */
    public function setDimensions(array $dimensions)
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    /**
     * @return VendorSpecific[]
     */
    public function getVendorspecifics()
    {
        if (!$this->vendorspecifics) {
            $this->vendorspecifics = array();
        }

        return $this->vendorspecifics;
    }

    /**
     * Sets vendorspecifics
     * @param ArrayCollection|DimensionInst[]|VendorSpecific[] $vendorspecifics
     * @return $this
     */
    public function setVendorspecifics(array $vendorspecifics)
    {
        $this->vendorspecifics = $vendorspecifics;
        return $this;
    }

    /**
     * Set layers
     *
     * @param WmsInstanceLayer[]|ArrayCollection $layers
     * @return $this
     */
    public function setLayers($layers)
    {
        $this->layers = $layers;
        return $this;
    }

    /**
     * @return WmsInstanceLayer[]|ArrayCollection
     */
    public function getLayers()
    {
        return $this->layers;
    }

    /**
     * Get root layer
     *
     * @return WmsInstanceLayer
     */
    public function getRootlayer()
    {
        foreach ($this->layers as $layer) {
            if ($layer->getParent() === null) {
                return $layer;
            }
        }
        return null;
    }

    /**
     * Set srs
     *
     * @param array $srs
     * @return $this
     */
    public function setSrs($srs)
    {
        $this->srs = $srs;
        return $this;
    }

    /**
     * Get srs
     *
     * @return array
     */
    public function getSrs()
    {
        return $this->srs;
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
        return $this->format !== null ? $this->format : 'image/png';
    }

    /**
     * Set infoformat
     *
     * @param string $infoformat
     * @return $this
     */
    public function setInfoformat($infoformat)
    {
        $this->infoformat = $infoformat;
        return $this;
    }

    /**
     * Get infoformat
     *
     * @return string
     */
    public function getInfoformat()
    {
        return $this->infoformat;
    }

    /**
     * Set exceptionformat
     *
     * @param string $exceptionformat
     * @return $this
     */
    public function setExceptionformat($exceptionformat)
    {
        $this->exceptionformat = $exceptionformat;
        return $this;
    }

    /**
     * Get exceptionformat
     *
     * @return string
     */
    public function getExceptionformat()
    {
        return $this->exceptionformat;
    }

    /**
     * Set transparency
     *
     * @param boolean $transparency
     * @return $this
     */
    public function setTransparency($transparency)
    {
        $this->transparency = (bool) $transparency;
        return $this;
    }

    /**
     * Get transparency
     *
     * @return boolean
     */
    public function getTransparency()
    {
        return $this->transparency;
    }

    /**
     * Set opacity
     *
     * @param integer $opacity
     * @return $this
     */
    public function setOpacity($opacity)
    {
        if (is_numeric($opacity)) {
            $this->opacity = $opacity;
        }
        return $this;
    }

    /**
     * Get opacity
     *
     * @return integer
     */
    public function getOpacity()
    {
        return $this->opacity;
    }

    /**
     * Set proxy
     *
     * @param boolean $proxy
     * @return $this
     */
    public function setProxy($proxy)
    {
        $this->proxy = (bool) $proxy;
        return $this;
    }

    /**
     * Get proxy
     *
     * @return boolean
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Set tiled
     *
     * @param boolean $tiled
     * @return $this
     */
    public function setTiled($tiled)
    {
        $this->tiled = (bool) $tiled;
        return $this;
    }

    /**
     * Get tiled
     *
     * @return boolean
     */
    public function getTiled()
    {
        return $this->tiled;
    }

    /**
     * Set ratio
     *
     * @param boolean $ratio
     * @return $this
     */
    public function setRatio($ratio)
    {
        if (is_numeric($ratio)) {
            $this->ratio = floatval($ratio);
        }

        return $this;
    }

    /**
     * Get ratio
     *
     * @return boolean
     */
    public function getRatio()
    {
        return $this->ratio;
    }

    /**
     * Set buffer
     *
     * @param boolean $buffer
     * @return $this
     */
    public function setBuffer($buffer)
    {
        $this->buffer = intval($buffer);
        return $this;
    }

    /**
     * Get buffer
     *
     * @return boolean
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Set wmssource
     *
     * @param WmsSource|null $source
     * @return $this
     */
    public function setSource($source = null)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Get wmssource
     *
     * @return WmsSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Add layers
     *
     * @param WmsInstanceLayer $layer
     * @return $this
     */
    public function addLayer(WmsInstanceLayer $layer)
    {
        $this->layers->add($layer);
        return $this;
    }

    /**
     * Remove layer
     * @param WmsInstanceLayer $layers
     * @return boolean true if layer removed
     */
    public function removeLayer(WmsInstanceLayer $layers)
    {
        return $this->layers->removeElement($layers);
    }

    public function getDisplayTitle()
    {
        $root = $this->getRootlayer();
        return $root->getTitle() ?: $root->getSourceItem()->getTitle() ?: $this->getTitle() ?: $this->getSource()->getTitle();
    }

    /**
     * Picks a reasonable first choice of GetMap image format for the given Source.
     * Picks (in order)
     * 1) png variants (robust transparency support)
     * 2) gif (high probability of transparency support)
     * 3) (giving up) first format parsed from source capabilities
     *
     * @param WmsSource $source
     * @return string|null
     * @todo: this belongs in the (only recently added) {@see SourceInstanceFactory}
     */
    protected function getDefaultGetMapFormat(WmsSource $source)
    {
        $formats = $source->getGetMap()->getFormats();
        if (!$formats) {
            // NOTE: this will implicitly trigger Openlayers default format (image/png)
            //       in the frontend
            return null;
        }
        foreach ($formats as $format) {
            // NOTE: also matches png8, png24, png32
            if (preg_match('#^image/png#', $format)) {
                return $format;
            }
        }
        foreach ($formats as $format) {
            if (preg_match('#^image/gif#', $format)) {
                return $format;
            }
        }
        // none of our preferences matches => return first declared format
        return $formats[0];
    }

    /**
     * @param WmsSource $source
     * @todo: this belongs in the (only recently added) {@see SourceInstanceFactory}
     */
    public function populateFromSource(WmsSource $source)
    {
        $this->setTitle($source->getTitle());
        $this->setFormat($this->getDefaultGetMapFormat($source));
        if ($source->getGetFeatureInfo() && $featureInfoFormats = $source->getGetFeatureInfo()->getFormats()) {
            $this->setInfoformat($featureInfoFormats[0]);
        }
        if ($exceptionFormats = $source->getExceptionFormats()) {
            $this->setExceptionformat($exceptionFormats[0]);
        }

        $this->setDimensions($source->dimensionInstancesFactory());
        // @todo: ??? why? is that safe?
        $this->setWeight(-1);

        $newRootLayer = new WmsInstanceLayer();
        $newRootLayer->populateFromSource($this, $source->getRootlayer());
    }

    /**
     * Returns desired layer order, as a string enum ('standard' or 'reverse')
     * NOTE: this is a recently added column; there will be NULLs in the DB for updated applications.
     *       The default for these cases is provided at the config service level.
     * @see WmsSourceService::getLayerConfiguration()
     *
     * @return string|null
     */
    public function getLayerOrder()
    {
        return $this->layerOrder;
    }

    /**
     * @param string $value use "conformant" or "traditional" (see consts)
     * @return $this
     * @throws \InvalidArgumentException if $value is not one of the expected values
     */
    public function setLayerOrder($value)
    {
        if (!in_array($value, $this->validLayerOrderChoices())) {
            throw new \InvalidArgumentException("Invalid layer order value '$value'");
        }
        $this->layerOrder = $value;
        return $this;
    }

    /**
     * @return string[]
     */
    public static function validLayerOrderChoices()
    {
        return array(
            self::LAYER_ORDER_TOP_DOWN,
            self::LAYER_ORDER_BOTTOM_UP,
        );
    }
}
