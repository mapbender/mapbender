<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\WmtsBundle\Entity\Style;
use Mapbender\WmtsBundle\Entity\WmtsSource;

/**
 * Description of WmtsLayerSource
 * @author Paul Schmidt
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_wmtslayersource")
 */
class WmtsLayerSource extends SourceItem # implements ContainingKeyword
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $wmts_tms;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $title = "";

    /**
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    protected $identifier = "";

    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $abstract = "";

    /**
     * @ORM\ManyToOne(targetEntity="WmtsSource",inversedBy="layers")
     * @ORM\JoinColumn(name="wmtssource", referencedColumnName="id")
     */
    protected $source; # change this variable name together with "get" "set" functions (s. SourceItem too)

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    public $latlonBounds;
    
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $boundingBoxes;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    public $styles;


    /**
     * @ORM\Column(type="array", nullable=true)
     */
    public $formats;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    public $infoformats;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    public $tilematrixSetlinks;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $dimensions;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $resourceUrl;

    /**
     * TODO: describe initial method
     */
    public function __construct($wmts_tms = WmtsSource::TYPE_WMTS)
    {
        $this->wmts_tms = $wmts_tms;
//        $this->keywords = new ArrayCollection();
        $this->infoformats = array();
        $this->formats = array();
        $this->styles = array();
        $this->dimension = array();
        $this->resourceUrl = array();
        $this->tilematrixSetlinks = array();
        $this->boundingBoxes = array();
    }
    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getWmtsTms()
    {
        return $this->wmts_tms;
    }

    public function setWmtsTms($wmts_tms)
    {
        $this->wmts_tms = $wmts_tms;
        return $this;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Get identifier
     *
     * @return string $identifier
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set abstract
     *
     * @param string $abstract
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
    }

    /**
     * Get abstract
     *
     * @return string $abstract
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * @inheritdoc
     */
    public function setSource(Source $wmtssource)
    {
        $this->source = $wmtssource;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set latlonBounds
     *
     * @param BoundingBox $latlonBounds
     * @return WmsLayerSource
     */
    public function setLatlonBounds(BoundingBox $latlonBounds = NULL)
    {
        $this->latlonBounds = $latlonBounds;
        return $this;
    }

    /**
     * Get latlonBounds
     *
     * @return BoundingBox
     */
    public function getLatlonBounds()
    {
        return $this->latlonBounds;
    }

    /**
     * Add boundingBox
     *
     * @param BoundingBox $boundingBoxes
     * @return WmsLayerSource
     */
    public function addBoundingBox(BoundingBox $boundingBoxes)
    {
        $this->boundingBoxes[] = $boundingBoxes;
        return $this;
    }

    /**
     * Set boundingBoxes
     *
     * @param array $boundingBoxes
     * @return WmsLayerSource
     */
    public function setBoundingBoxes($boundingBoxes)
    {
        $this->boundingBoxes = $boundingBoxes ? $boundingBoxes : array();
        return $this;
    }

    /**
     * Get boundingBoxes
     *
     * @return array
     */
    public function getBoundingBoxes()
    {
        return $this->boundingBoxes;
    }

    /**
     * Set styles
     * @param array $styles
     * @return WmtsSource
     */
    public function setStyles($styles)
    {
        $this->styles = $styles;
        return $this;
    }

    /**
     * Add style
     * @param Style $style
     * @return WmtsSource
     */
    public function addStyle($style)
    {
        $this->styles[] = $style;
        return $this;
    }

    /**
     * Get styles
     *
     * @return Style[]
     */
    public function getStyles()
    {
        return $this->styles;
    }


    /**
     * Set formats
     *
     * @param array $formats
     * @return WmtsSource
     */
    public function setFormats($formats)
    {
        $this->formats = $formats;
        return $this;
    }

    /**
     * Add format
     *
     * @param array $format
     * @return WmtsSource
     */
    public function addFormat($format)
    {
        $this->formats[] = $format;
        return $this;
    }

    /**
     * Get formats
     *
     * @return array
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * Set infoformats
     *
     * @param array $infoformats
     * @return WmtsSource
     */
    public function setInfoformats($infoformats)
    {
        $this->infoformats = $infoformats;
        return $this;
    }

    /**
     * Add infoformat
     *
     * @param string $infoformat
     * @return WmtsSource
     */
    public function addInfoformat($infoformat)
    {
        $this->infoformats[] = $infoformat;
        return $this;
    }

    /**
     * Get infoformats
     *
     * @return array
     */
    public function getInfoformats()
    {
        return $this->infoformats;
    }

    /**
     *Gets tilematrixSetlinks.
     * @return \Mapbender\WmtsBundle\Entity\TileMatrixSetLink[]
     */
    public function getTilematrixSetlinks()
    {
        return $this->tilematrixSetlinks;
    }

    /**
     * Sets tilematrixSetlinks
     * @param \Mapbender\WmtsBundle\Entity\TileMatrixSetLink $tilematrixSetlinks
     * @return \Mapbender\WmtsBundle\Entity\WmtsLayerSource
     */
    public function setTilematrixSetlinks(array $tilematrixSetlinks = array())
    {
        $this->tilematrixSetlinks = $tilematrixSetlinks;
        return $this;
    }

    /**
     * Adds TileMatrixSetLink.
     * @param \Mapbender\WmtsBundle\Entity\TileMatrixSetLink $tilematrixSetlinks
     * @return \Mapbender\WmtsBundle\Entity\WmtsLayerSource
     */
    public function addTilematrixSetlinks(TileMatrixSetLink $tilematrixSetlinks)
    {
        $this->tilematrixSetlinks[] = $tilematrixSetlinks;
        return $this;
    }


    /**
     * Gets dimensions.
     * @return Dimension[]
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * Sets dimensions.
     * @param array $dimensions
     * @return \Mapbender\WmtsBundle\Entity\WmtsLayerSource
     */
    public function setDimensions(array $dimensions = array())
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    /**
     * Adds dimension.
     * @param Dimension $dimension
     * @return \Mapbender\WmtsBundle\Entity\WmtsLayerSource
     */
    public function addDimension($dimension)
    {
        $this->dimensions[] = $dimension;
        return $this;
    }

    /**
     * Set resourceUrl
     * @param array $resourceUrls
     * @return \Mapbender\WmtsBundle\Entity\WmtsLayerSource
     */
    public function setResourceUrl(array $resourceUrls = array())
    {
        $this->resourceUrl = $resourceUrls;
        return $this;
    }

    /**
     * Add resourceUrl
     * @param string $resourceUrl
     * @return $this resourceUrl
     */
    public function addResourceUrl(UrlTemplateType $resourceUrl)
    {
        $this->resourceUrl[] = $resourceUrl;
        return $this;
    }

    /**
     * Get resourceUrl
     *
     * @return array resourceUrl
     */
    public function getResourceUrl()
    {
        return $this->resourceUrl;
    }
}
