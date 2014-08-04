<?php

namespace Mapbender\WmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\ExchangeIn;
use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Component\Style;
use Mapbender\WmsBundle\Component\WmsInstanceConfiguration;
use Mapbender\WmsBundle\Component\WmsInstanceConfigurationOptions;
use Mapbender\WmsBundle\Component\WmsMetadata;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsSource;

/**
 * WmsInstance class
 *
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_wms_wmsinstance")
 * ORM\DiscriminatorMap({"mb_wms_wmssourceinstance" = "WmsSourceInstance"})
 */
class WmsInstance extends SourceInstance implements ExchangeIn
{

    /**
     * @var array $configuration The instance configuration
     * @ORM\Column(type="array", nullable=true)
     */
    protected $configuration;

    /**
     * @ORM\ManyToOne(targetEntity="WmsSource", inversedBy="wmsinstance", cascade={"refresh"})
     * @ORM\JoinColumn(name="wmssource", referencedColumnName="id")
     */
    protected $source;

    /**
     * @ORM\OneToMany(targetEntity="WmsInstanceLayer", mappedBy="wmsinstance", cascade={"refresh", "persist", "remove"})
     * @ORM\JoinColumn(name="layers", referencedColumnName="id")
     * @ORM\OrderBy({"priority" = "asc"})
     */
    protected $layers; //{ name: 1,   title: Webatlas,   visible: true }

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
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $visible = true;

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

    public function __construct()
    {
        $this->layers = new ArrayCollection();
    }

    /**
     * Set id
     * @param integer $id
     * @return WmsInstance
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set configuration
     *
     * @param array $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * Get an Instance Configuration.
     *
     * @return array $configuration
     */
    public function getConfiguration(Signer $signer = null)
    {
        if ($this->getSource() === null) { // from yaml
            $this->generateYmlConfiguration();
        } else {
            if ($this->configuration === null) {
                $this->generateConfiguration();
            }
        }

        if ($signer) {
            $this->configuration['options']['url'] = $signer->signUrl($this->configuration['options']['url']);
            if ($this->proxy) {
                $this->signeUrls($signer, $this->configuration['children'][0]);
            }
        }

        return $this->configuration;
    }

    private function signeUrls(Signer $signer, &$layer)
    {
        if (isset($layer['options']['legend'])) {
            if (isset($layer['options']['legend']['graphic'])) {
                $layer['options']['legend']['graphic'] = $signer->signUrl($layer['options']['legend']['graphic']);
            } else if (isset($layer['options']['legend']['url'])) {
                $layer['options']['legend']['url'] = $signer->signUrl($layer['options']['legend']['url']);
            }
        }
        if (isset($layer['children'])) {
            foreach ($layer['children'] as &$child) {
                $this->signeUrls($signer, $child);
            }
        }
    }

    /**
     * Generates a configuration from an yml file
     */
    public function generateYmlConfiguration()
    {
        $this->setSource(new WmsSource());
        $wmsconf = new WmsInstanceConfiguration();
        $wmsconf->setType(strtolower($this->getType()));
        $wmsconf->setTitle($this->title);
        $wmsconf->setIsBaseSource($this->isBasesource());

        $options = new WmsInstanceConfigurationOptions();
        $options->setUrl($this->configuration["url"])
            ->setProxy($this->proxy)
            ->setVisible($this->visible)
            ->setFormat($this->getFormat())
            ->setInfoformat($this->infoformat)
            ->setTransparency($this->transparency)
            ->setOpacity($this->opacity / 100)
            ->setTiled($this->tiled);

        if (isset($this->configuration["vendor"])) {
            $options->setVendor($this->configuration["vendor"]);
        }

        $wmsconf->setOptions($options);

        if (!key_exists("children", $this->configuration)) {
            $num = 0;
            $rootlayer = new WmsInstanceLayer();
            $rootlayer->setTitle($this->title)
                ->setId($this->getId() . "_" . $num)
                ->setMinScale(!isset($this->configuration["minScale"]) ? null : $this->configuration["minScale"])
                ->setMaxScale(!isset($this->configuration["maxScale"]) ? null : $this->configuration["maxScale"])
                ->setSelected(!isset($this->configuration["visible"]) ? false : $this->configuration["visible"])
                ->setPriority($num)
                ->setWmslayersource(new WmsLayerSource())
                ->setWmsInstance($this);
            $rootlayer->setToggle(false);
            $rootlayer->setAllowtoggle(true);
            $this->addLayer($rootlayer);
            foreach ($this->configuration["layers"] as $layerDef) {
                $num++;
                $layer = new WmsInstanceLayer();
                $layersource = new WmsLayerSource();
                $layersource->setName($layerDef["name"]);
                if (isset($layerDef["legendurl"])) {
                    $style = new Style();
                    $style->setName(null);
                    $style->setTitle(null);
                    $style->setAbstract(null);
                    $legendUrl = new LegendUrl();
                    $legendUrl->setWidth(null);
                    $legendUrl->setHeight(null);
                    $onlineResource = new OnlineResource();
                    $onlineResource->setFormat(null);
                    $onlineResource->setHref($layerDef["legendurl"]);
                    $legendUrl->setOnlineResource($onlineResource);
                    $style->setLegendUrl($legendUrl);
                    $layersource->addStyle($style);
                }
                $layer->setTitle($layerDef["title"])
                    ->setId($this->getId() . '-' . $num)
                    ->setMinScale(!isset($layerDef["minScale"]) ? null : $layerDef["minScale"])
                    ->setMaxScale(!isset($layerDef["maxScale"]) ? null : $layerDef["maxScale"])
                    ->setSelected(!isset($layerDef["visible"]) ? false : $layerDef["visible"])
                    ->setInfo(!isset($layerDef["queryable"]) ? false : $layerDef["queryable"])
                    ->setParent($rootlayer)
                    ->setWmslayersource($layersource)
                    ->setWmsInstance($this);
                $layer->setAllowinfo($layer->getInfo() !== null && $layer->getInfo() ? true : false);
                $rootlayer->addSublayer($layer);
                $this->addLayer($layer);
            }
            $children = array($this->generateLayersConfiguration($rootlayer));
            $wmsconf->setChildren($children);
        } else {
            $wmsconf->setChildren($this->configuration["children"]);
        }
        $this->configuration = $wmsconf->toArray();
    }

    /**
     * Generates a configuration
     */
    public function generateConfiguration()
    {
        $rootlayer = $this->getRootlayer();
        $llbbox = $rootlayer->getWmslayersource()->getLatlonBounds();
        $srses = array(
            $llbbox->getSrs() => array(
                floatval($llbbox->getMinx()),
                floatval($llbbox->getMiny()),
                floatval($llbbox->getMaxx()),
                floatval($llbbox->getMaxy())
            )
        );
        foreach ($rootlayer->getWmslayersource()->getBoundingBoxes() as $bbox) {
            $srses = array_merge($srses, array($bbox->getSrs() => array(
                    floatval($bbox->getMinx()),
                    floatval($bbox->getMiny()),
                    floatval($bbox->getMaxx()),
                    floatval($bbox->getMaxy()))));
        }
        $wmsconf = new WmsInstanceConfiguration();
        $wmsconf->setType(strtolower($this->getType()));
        $wmsconf->setTitle($this->title);
        $wmsconf->setIsBaseSource($this->isBasesource());

        $options = new WmsInstanceConfigurationOptions();
        $options->setUrl($this->source->getGetMap()->getHttpGet())
            ->setProxy($this->getProxy())
            ->setVisible($this->getVisible())
            ->setFormat($this->getFormat())
            ->setInfoformat($this->getInfoformat())
            ->setTransparency($this->transparency)
            ->setOpacity($this->opacity / 100)
            ->setTiled($this->tiled)
            ->setBbox($srses);
        $wmsconf->setOptions($options);
        $wmsconf->setChildren(array($this->generateLayersConfiguration($rootlayer)));
        $this->configuration = $wmsconf->toArray();
    }

    /**
     * Generates a configuration for layers
     *
     * @param WmsInstanceLayer $layer
     * @param array $configuration
     * @return array
     */
    public function generateLayersConfiguration(WmsInstanceLayer $layer, $configuration = array())
    {
        if ($layer->getActive() === true) {
            $children = array();
            if ($layer->getSublayer()->count() > 0) {
                foreach ($layer->getSublayer() as $sublayer) {
                    $configurationTemp = $this->generateLayersConfiguration($sublayer);
                    if (count($configurationTemp) > 0) {
                        $children[] = $configurationTemp;
                    }
                }
            }
            $layerConf = $layer->getConfiguration();
            $configuration = array(
                "options" => $layerConf,
                "state" => array(
                    "visibility" => null,
                    "info" => null,
                    "outOfScale" => null,
                    "outOfBounds" => null),);
            if (count($children) > 0) {
                $configuration["children"] = $children;
            }
        }
        return $configuration;
    }

    /**
     * Set layers
     *
     * @param array $layers
     * @return WmsInstance
     */
    public function setLayers($layers)
    {
        $this->layers = $layers;

        return $this;
    }

    /**
     * Get layers
     *
     * @return array
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
     * Set title
     *
     * @param string $title
     * @return WmsInstance
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set srs
     *
     * @param array $srs
     * @return WmsInstance
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
     * @return WmsInstance
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
     * @return WmsInstance
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
     * @return WmsInstance
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
     * @return WmsInstance
     */
    public function setTransparency($transparency)
    {
        $this->transparency = $transparency;

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
     * Set visible
     *
     * @param boolean $visible
     * @return WmsInstance
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible
     *
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set opacity
     *
     * @param integer $opacity
     * @return WmsInstance
     */
    public function setOpacity($opacity)
    {
        $this->opacity = $opacity;

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
     * @return WmsInstance
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;

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
     * @return WmsInstance
     */
    public function setTiled($tiled)
    {
        $this->tiled = $tiled;

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
     * Set wmssource
     *
     * @param WmsSource $wmssource
     * @return WmsInstance
     */
    public function setSource(WmsSource $wmssource = null)
    {
        $this->source = $wmssource;

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
     * @param WmsInstanceLayer $layers
     * @return WmsInstance
     */
    public function addLayer(WmsInstanceLayer $layer)
    {
        $this->layers->add($layer);

        return $this;
    }

    /**
     * Remove layers
     *
     * @param WmsInstanceLayer $layers
     */
    public function removeLayer(WmsInstanceLayer $layers)
    {
        $this->layers->removeElement($layers);
    }

    public function __toString()
    {
        return $this->getId();
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return "wms";
    }

    /**
     * @inheritdoc
     */
    public function getManagerType()
    {
        return "wms";
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array(
            'js' => array(
                '@MapbenderWmsBundle/Resources/public/mapbender.source.wms.js'),
            'css' => array(),
            'trans' => array('MapbenderWmsBundle::wmsbundle.json.twig'));
    }

    /**
     * @inheritdoc
     */
    public function getLayerset()
    {
        parent::getLayerset();
    }

    /**
     * 
     * @return WmsMetadata
     */
    public function getMetadata()
    {
        return new WmsMetadata($this);
    }

    /**
     * @inheritdoc
     */
    public function remove(EntityManager $em)
    {
        $this->removeLayerRecursive($em, $this->getRootlayer());
        $em->remove($this);
    }

    /**
     * Recursively remove a nested Layerstructure
     * @param EntityManager $em
     * @param WmsInstanceLayer $instLayer
     */
    private function removeLayerRecursive(EntityManager $em, WmsInstanceLayer $instLayer)
    {
        foreach ($instLayer->getSublayer() as $sublayer) {
            $this->removeLayerRecursive($em, $sublayer);
        }
        $em->remove($instLayer);
        $em->flush();
    }

    /**
     * @inheritdoc
     */
    public function copy(EntityManager $em)
    {
        $inst = new WmsInstance();
        $inst->title = $this->title;
        $inst->weight = $this->weight;
        $inst->enabled = $this->enabled;
        $inst->configuration = $this->configuration; //???
        $inst->source = $this->source;
        $inst->srs = $this->srs;
        $inst->format = $this->format;
        $inst->infoformat = $this->infoformat;
        $inst->exceptionformat = $this->exceptionformat;
        $inst->transparency = $this->transparency;
        $inst->visible = $this->visible;
        $inst->opacity = $this->opacity;
        $inst->proxy = $this->proxy;
        $inst->tiled = $this->tiled;
        $this->copyLayerRecursive($em, $inst, $this->getRootlayer(), NULL);
        return $inst;
    }

    /**
     * Recursively copy a nested Layerstructure
     * @param EntityManager $em
     * @param WmsInstanceLayer $instLayer
     */
    private function copyLayerRecursive(EntityManager $em, WmsInstance $instCloned, WmsInstanceLayer $origin,
        WmsInstanceLayer $clonedParent = null)
    {
        $cloned = $origin->copy($em);
        $cloned->setWmsinstance($instCloned);
        $cloned->setWmslayersource($origin->getWmslayersource());
        if ($clonedParent !== null) {
            $cloned->setParent($clonedParent);
            $clonedParent->addSublayer($cloned);
        }
        $instCloned->addLayer($cloned);
        foreach ($origin->getSublayer() as $sublayer) {
            $this->copyLayerRecursive($em, $instCloned, $sublayer, $cloned);
        }
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        $arr = array();
        $arr['__class__'] =  get_class($this);
        $arr['id'] =  $this->id;
        $arr['title'] =  $this->title;
        $arr['configuration'] =  $this->configuration;
        $arr['source'] =  $this->getSource()->getId();
        $arr['srs'] =  $this->srs;
        $arr['format'] =  $this->format;
        $arr['infoformat'] =  $this->infoformat;
        $arr['exceptionformat'] =  $this->exceptionformat;
        $arr['transparency'] =  $this->transparency;
        $arr['visible'] =  $this->visible;
        $arr['opacity'] =  $this->opacity;
        $arr['proxy'] =  $this->proxy;
        $arr['tiled'] =  $this->tiled;
        $arr['layers'] =  array();
        foreach ($this->getLayers() as $layer) {
            $arr['layers'][] = $layer->toArray();
        }
        return $arr;
    }
    
    /**
     * @inheritdoc
     */
    public static function fromArray(array $serialized)
    {
        
    }

}
