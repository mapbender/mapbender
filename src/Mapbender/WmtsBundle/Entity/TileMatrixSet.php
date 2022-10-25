<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;
use Mapbender\WmtsBundle\Component\TileMatrix;

/**
 * A TileMatrixSet entity describes a particular set of tile matrices.
 * @author Paul Schmidt
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_tilematrixset")
 */
class TileMatrixSet implements MutableUrlTarget
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

    public function __construct()
    {
        $this->tilematrices = array();
    }
    
    /**
     * @return integer TileMatrixSet id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @return HttpTileSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     *
     * @param HttpTileSource $wmtssource
     * @return TileMatrixSet
     */
    public function setSource(HttpTileSource $wmtssource)
    {
        $this->source = $wmtssource;
        return $this;
    }

    /**
     * @return string supportedCrs
     */
    public function getSupportedCrs()
    {
        return $this->supportedCrs;
    }
    
    /**
     * @param string $supportedCrs
     * @return $this
     */
    public function setSupportedCrs($supportedCrs)
    {
        $this->supportedCrs = $supportedCrs;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $value
     */
    public function setTitle($value)
    {
        $this->title = $value;
    }

    /**
     * @return string
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * @param string $value
     */
    public function setAbstract($value)
    {
        $this->abstract = $value;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $value
     */
    public function setIdentifier($value)
    {
        $this->identifier = $value;
    }

    /**
     * @return TileMatrix[]
     */
    public function getTilematrices()
    {
        return $this->tilematrices;
    }

    /**
     * @param TileMatrix[] $tilematrices
     */
    public function setTilematrices($tilematrices)
    {
        $this->tilematrices = $tilematrices;
    }

    /**
     * @param TileMatrix $tilematrix
     */
    public function addTilematrix(TileMatrix $tilematrix)
    {
        $this->tilematrices[] = $tilematrix;
    }

    /**
     * Returns the id, stringified.
     * @return string
     */
    public function __toString()
    {
        return (string) $this->id;
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        $tileMatricesNew = array();
        foreach ($this->getTilematrices() as $tileMatrix) {
            $tileMatrix->mutateUrls($transformer);
            $tileMatricesNew[] = clone $tileMatrix;
        }
        $this->setTilematrices($tileMatricesNew);
    }
}
