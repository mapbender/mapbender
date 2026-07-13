<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;
use Mapbender\WmtsBundle\Component\TileMatrix;

/**
 * A TileMatrixSet entity describes a particular set of tile matrices.
 * @author Paul Schmidt
 */
#[ORM\Entity]
#[ORM\Table(name: 'mb_wmts_tilematrixset')]
class TileMatrixSet implements MutableUrlTarget, \Stringable
{

    /**
     * @var integer $id
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\ManyToOne(targetEntity: WmtsSource::class, inversedBy: 'tilematrixsets')]
    #[ORM\JoinColumn(name: 'wmtssource', referencedColumnName: 'id')]
    protected $source;

    /**
     * Tile matrix set identifier
     */
    #[ORM\Column(type: 'string', nullable: false)]
    protected $identifier;

    #[ORM\Column(type: 'string', nullable: true)]
    protected $title;

    #[ORM\Column(type: 'text', nullable: true)]
    protected $abstract;

    #[ORM\Column(type: 'string', nullable: false)]
    protected $supportedCrs;

    #[ORM\Column(type: 'json', nullable: false)] // ;
    protected array $tilematrices;

    public function __construct()
    {
        $this->tilematrices = [];
    }

    /**
     * @return integer TileMatrixSet id
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;
        return $this;
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
    public function setSource(HttpTileSource $wmtssource): static
    {
        $this->source = $wmtssource;
        return $this;
    }

    /**
     * @return string supportedCrs
     */
    public function getSupportedCrs()
    {
        return str_contains((string) $this->supportedCrs, "CRS84") ? "EPSG:4326" : $this->supportedCrs;
    }

    /**
     * @param string $supportedCrs
     * @return $this
     */
    public function setSupportedCrs($supportedCrs): static
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
    public function setTitle($value): void
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
    public function setAbstract($value): void
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
    public function setIdentifier($value): void
    {
        $this->identifier = $value;
    }

    /**
     * @return TileMatrix[]
     */
    public function getTilematrices(): array
    {
        $result = [];
        foreach ($this->tilematrices ?? [] as $item) {
            if ($item instanceof TileMatrix) {
                $result[] = $item;
            } elseif (is_array($item)) {
                $tm = new TileMatrix();
                $tm->setIdentifier($item['identifier'] ?? null);
                if (isset($item['scaledenominator'])) $tm->setScaledenominator($item['scaledenominator']);
                if (isset($item['href'])) $tm->setHref($item['href']);
                if (isset($item['topleftcorner'])) $tm->setTopleftcorner($item['topleftcorner']);
                if (isset($item['tilewidth'])) $tm->setTilewidth($item['tilewidth']);
                if (isset($item['tileheight'])) $tm->setTileheight($item['tileheight']);
                if (isset($item['matrixwidth'])) $tm->setMatrixwidth($item['matrixwidth']);
                if (isset($item['matrixheight'])) $tm->setMatrixheight($item['matrixheight']);
                $result[] = $tm;
            }
        }
        return $result;
    }

    /**
     * @param TileMatrix[] $tilematrices
     */
    public function setTilematrices($tilematrices): void
    {
        $this->tilematrices = $tilematrices;
    }

    /**
     * @param TileMatrix $tilematrix
     */
    public function addTilematrix(TileMatrix $tilematrix): void
    {
        $this->tilematrices[] = $tilematrix;
    }

    /**
     * Returns the id, stringified.
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function mutateUrls(OneWayTransformer $transformer): void
    {
        $tileMatricesNew = [];
        foreach ($this->getTilematrices() as $tileMatrix) {
            $tileMatrix->mutateUrls($transformer);
            $tileMatricesNew[] = clone $tileMatrix;
        }
        $this->setTilematrices($tileMatricesNew);
    }
}
