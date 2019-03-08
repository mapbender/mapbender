<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Mapbender\WmtsBundle\Component\TileMatrix;
use Mapbender\WmtsBundle\Entity\WmtsSource;

/**
 * A TileMatrixSet entity describes a particular set of tile matrices.
 * @author Paul Schmidt
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_tilematrixset")
 * ORM\DiscriminatorMap({"mb_wmts_tilematrixset" = "TileMatrixSet"})
 */
class TileMatrixSet
{

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="WmtsSource",inversedBy="tilematrixsets")
     * @ORM\JoinColumn(name="wmtssource", referencedColumnName="id")
     */
    protected $source;

    /**
     * Tile matrix set identifier
     * @ORM\Column(type="string",nullable=false)
     */
    protected $identifier;

    /**
     * Minimum bounding rectangle surrounding the visible layer presented by this tile matrix set, in the supported CRS
     * @var type
     */
    protected $boundingBox;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $title;

    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $abstract;

    /**
     * @ORM\Column(type="string",nullable=false)
     */
    protected $supportedCrs;

    /**
     * @ORM\Column(type="array",nullable=false);
     */
    protected $tilematrices;

    /**
     * Create an instance of TileMatrixSet
     */
    public function __construct()
    {
        $this->tilematrices = array();
    }
    
    /**
     * Get id
     * @return integer TileMatrixSet id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @return TileMatrixSet
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     *
     * @param WmtsSource $wmtssource
     * @return TileMatrixSet
     */
    public function setSource(WmtsSource $wmtssource)
    {
        $this->source = $wmtssource;
        return $this;
    }

    /**
     * Get supportedCrs
     * @return string supportedCrs
     */
    public function getSupportedCrs()
    {
        return $this->supportedCrs;
    }
    
    /**
     * Set supportedCrs
     * @param string $supportedCrs
     * @return \Mapbender\WmtsBundle\Entity\TileMatrixSet
     */
    public function setSupportedCrs($supportedCrs)
    {
        $this->supportedCrs = $supportedCrs;
        return $this;
    }

        /**
     * Get title
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title
     * @param string $value
     */
    public function setTitle($value)
    {
        $this->title = $value;
    }

    /**
     * Get abstract
     * @return string
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * Set abstract
     * @param string $value
     */
    public function setAbstract($value)
    {
        $this->abstract = $value;
    }

    /**
     * Get identifier
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set identifier
     * @param string $value
     */
    public function setIdentifier($value)
    {
        $this->identifier = $value;
    }

    /**
     * Get keyword
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Set keyword
     * @param string $value
     */
    public function setKeyword($value)
    {
        $this->keyword = $value;
    }
    
    /**
     * Get tilematrices
     * @return TileMatrix[]
     */
    public function getTilematrices()
    {
        return $this->tilematrices;
    }

    /**
     * Set tilematrices
     * @param array $tilematrices
     */
    public function setTilematrices($tilematrices)
    {
        $this->tilematrices = $tilematrices;
    }

    /**
     * Add a tilematrix
     * @param TileMatrix $tilematrix
     */
    public function addTilematrix(TileMatrix $tilematrix)
    {
        $this->tilematrices[] = $tilematrix;
    }

    /**
     * Get boundingbox
     * @return array
     */
    public function getBoundingbox()
    {
        return $this->boundingbox;
    }

    /**
     * Set boundingbox.
     * @param array $boundingbox
     */
    public function setBoundingbox($boundingbox)
    {
        $this->boundingbox = $boundingbox;
    }

    /**
     * Returns a TileMatrixSet as String
     *
     * @return String TileMatrixSet as String
     */
    public function __toString()
    {
        return (string) $this->id;
    }
}
