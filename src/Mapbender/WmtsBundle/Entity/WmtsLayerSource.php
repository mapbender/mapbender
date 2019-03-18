<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\WmtsBundle\Component\Style;
use Mapbender\WmtsBundle\Component\TileMatrixSetLink;
use Mapbender\WmtsBundle\Component\UrlTemplateType;


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

    public function __construct()
    {
//        $this->keywords = new ArrayCollection();
        $this->infoformats = array();
        $this->styles = array();
        $this->dimensions = array();
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setBoundingBoxes($boundingBoxes)
    {
        $this->boundingBoxes = $boundingBoxes ? $boundingBoxes : array();
        return $this;
    }

    /**
     * Get boundingBoxes
     *
     * @return BoundingBox[]
     */
    public function getBoundingBoxes()
    {
        return $this->boundingBoxes;
    }

    /**
     * Set styles
     * @param array $styles
     * @return $this
     */
    public function setStyles($styles)
    {
        $this->styles = $styles;
        return $this;
    }

    /**
     * Add style
     * @param Style $style
     * @return $this
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
     * Set infoformats
     *
     * @param array $infoformats
     * @return $this
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
     * @return $this
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
     * @return TileMatrixSetLink[]
     */
    public function getTilematrixSetlinks()
    {
        return $this->tilematrixSetlinks;
    }

    /**
     * Sets tilematrixSetlinks
     * @param TileMatrixSetLink[] $tilematrixSetlinks
     * @return $this
     */
    public function setTilematrixSetlinks(array $tilematrixSetlinks = array())
    {
        $this->tilematrixSetlinks = $tilematrixSetlinks;
        return $this;
    }

    /**
     * Adds TileMatrixSetLink.
     * @param TileMatrixSetLink $tilematrixSetlink
     * @return $this
     */
    public function addTilematrixSetlinks(TileMatrixSetLink $tilematrixSetlink)
    {
        $this->tilematrixSetlinks[] = $tilematrixSetlink;
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
     * @param Dimension[] $dimensions
     * @return $this
     */
    public function setDimensions(array $dimensions = array())
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    /**
     * Adds dimension.
     * @param Dimension $dimension
     * @return $this
     */
    public function addDimension($dimension)
    {
        $this->dimensions[] = $dimension;
        return $this;
    }

    /**
     * Set resourceUrl
     * @param UrlTemplateType[] $resourceUrls
     * @return $this
     */
    public function setResourceUrl(array $resourceUrls = array())
    {
        $this->resourceUrl = $resourceUrls;
        return $this;
    }

    /**
     * Add resourceUrl
     * @param UrlTemplateType $resourceUrl
     * @return $this
     */
    public function addResourceUrl(UrlTemplateType $resourceUrl)
    {
        $this->resourceUrl[] = $resourceUrl;
        return $this;
    }

    /**
     * Get resourceUrl
     *
     * @return UrlTemplateType[] resourceUrl
     */
    public function getResourceUrl()
    {
        return $this->resourceUrl;
    }

    /**
     * Returns a merged array of the latlon bounds (if set) and other bounding boxes.
     * This is used by the *EntityHandler machinery frontend config generation.
     *
     * @return BoundingBox[]
     */
    public function getMergedBoundingBoxes()
    {
        $bboxes = array();
        $latLonBounds = $this->getLatlonBounds();
        if ($latLonBounds) {
            $bboxes[] = $latLonBounds;
        }
        return array_merge($bboxes, $this->getBoundingBoxes());
    }

    /**
     * @return string[]
     */
    public function getUniqueTileFormats()
    {
        $formats = array();
        foreach ($this->getResourceUrl() as $resourceUrl) {
            $resourceType = $resourceUrl->getResourceType() ?: 'tile';
            if ($resourceType === 'tile') {
                $formats[] = $resourceUrl->getFormat();
            }
        }
        return array_unique($formats);
    }
}
