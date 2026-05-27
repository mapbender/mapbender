<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\WmtsBundle\Component\Presenter\ConfigGeneratorCommon;
use Mapbender\WmtsBundle\Component\Style;
use Mapbender\WmtsBundle\Component\TileMatrixSetLink;
use Mapbender\WmtsBundle\Component\UrlTemplateType;


/**
 * @author Paul Schmidt
 *
 * @property WmtsSource $source
 * @method WmtsSource getSource
 */
#[ORM\Entity]
#[ORM\Table(name: 'mb_wmts_wmtslayersource')]
class WmtsLayerSource extends SourceItem implements MutableUrlTarget
{

    #[ORM\Column(name: 'name', type: 'string', nullable: true)]
    protected $identifier = "";

    #[ORM\Column(type: 'text', nullable: true)]
    protected $abstract = "";

    #[ORM\ManyToOne(targetEntity: WmtsSource::class, inversedBy: 'layers')]
    #[ORM\JoinColumn(name: 'wmtssource', referencedColumnName: 'id')]
    protected $source;

    #[ORM\Column(type: 'json', nullable: true)]
    protected $latlonBounds;

    #[ORM\Column(type: 'json', nullable: true)]
    protected $boundingBoxes;

    #[ORM\Column(type: 'json', nullable: true)]
    protected $styles;

    #[ORM\Column(type: 'json', nullable: true)]
    protected $infoformats;

    #[ORM\Column(type: 'json', nullable: true)]
    protected $tilematrixSetlinks;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected $priority;

    public function setPriority(mixed $priority): self
    {
        $this->priority = $priority !== null ? intval($priority) : $priority;
        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * @var UrlTemplateType[]
     */
    #[ORM\Column(type: 'json', nullable: true)]
    protected $resourceUrl;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'sublayer')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['priority' => 'asc', 'id' => 'asc'])]
    protected $sublayer;

    public function __construct()
    {
        $this->sublayer = new ArrayCollection();
        $this->infoformats = array();
        $this->styles = array();
        $this->resourceUrl = array();
        $this->tilematrixSetlinks = array();
        $this->boundingBoxes = array();
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string $identifier
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $abstract
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
    }

    /**
     * @return string $abstract
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * @param BoundingBox|null $latlonBounds
     * @return $this
     */
    public function setLatlonBounds(?BoundingBox $latlonBounds = NULL)
    {
        $this->latlonBounds = $latlonBounds;
        return $this;
    }

    /**
     * @return BoundingBox|null
     */
    public function getLatlonBounds()
    {
        if (is_array($this->latlonBounds)) {
            return BoundingBox::create($this->latlonBounds);
        }
        return $this->latlonBounds;
    }

    /**
     * @param BoundingBox $boundingBoxes
     * @return $this
     */
    public function addBoundingBox(BoundingBox $boundingBoxes)
    {
        $this->boundingBoxes[] = $boundingBoxes;
        return $this;
    }

    /**
     * @param BoundingBox[] $boundingBoxes
     */
    public function setBoundingBoxes($boundingBoxes)
    {
        $this->boundingBoxes = $boundingBoxes ?: array();
    }

    /**
     * @return BoundingBox[]
     */
    public function getBoundingBoxes()
    {
        $result = [];
        foreach ($this->boundingBoxes ?? [] as $item) {
            if ($item instanceof BoundingBox) {
                $result[] = $item;
            } elseif (is_array($item)) {
                $result[] = BoundingBox::create($item);
            }
        }
        return $result;
    }

    /**
     * @param array $styles
     * @return $this
     */
    public function setStyles($styles)
    {
        $this->styles = $styles;
        return $this;
    }

    /**
     * @param Style $style
     * @return $this
     */
    public function addStyle($style)
    {
        $this->styles[] = $style;
        return $this;
    }

    /**
     * @return Style[]
     */
    public function getStyles()
    {
        $result = [];
        foreach ($this->styles ?? [] as $item) {
            if ($item instanceof Style) {
                $result[] = $item;
            } elseif (is_array($item)) {
                $style = new Style();
                $style->setIsDefault($item['isDefault'] ?? null);
                $style->setTitle($item['title'] ?? null);
                $style->setAbstract($item['abstract'] ?? null);
                $style->setIdentifier($item['identifier'] ?? null);
                if (!empty($item['legendurl']) && is_array($item['legendurl'])) {
                    $legendUrl = new LegendUrl();
                    $legendUrl->setFormat($item['legendurl']['format'] ?? null);
                    $legendUrl->setHref($item['legendurl']['href'] ?? null);
                    $style->setLegendurl($legendUrl);
                }
                $result[] = $style;
            }
        }
        return $result;
    }


    /**
     * @param array $infoformats
     * @return $this
     */
    public function setInfoformats($infoformats)
    {
        $this->infoformats = $infoformats;
        return $this;
    }

    /**
     * @param string $infoformat
     * @return $this
     */
    public function addInfoformat($infoformat)
    {
        $this->infoformats[] = $infoformat;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getInfoformats()
    {
        return $this->infoformats;
    }

    /**
     * @return TileMatrixSetLink[]
     */
    public function getTilematrixSetlinks()
    {
        $result = [];
        foreach ($this->tilematrixSetlinks ?? [] as $item) {
            if ($item instanceof TileMatrixSetLink) {
                $result[] = $item;
            } elseif (is_array($item)) {
                $link = new TileMatrixSetLink();
                $link->setTileMatrixSet($item['tileMatrixSet'] ?? null);
                $link->setTileMatrixSetLimits($item['tileMatrixSetLimits'] ?? null);
                $result[] = $link;
            }
        }
        return $result;
    }

    /**
     * @param TileMatrixSetLink[] $tilematrixSetlinks
     * @return $this
     */
    public function setTilematrixSetlinks(array $tilematrixSetlinks = array())
    {
        $this->tilematrixSetlinks = $tilematrixSetlinks;
        return $this;
    }

    /**
     * @param TileMatrixSetLink $tilematrixSetlink
     * @return $this
     */
    public function addTilematrixSetlinks(TileMatrixSetLink $tilematrixSetlink)
    {
        $this->tilematrixSetlinks[] = $tilematrixSetlink;
        return $this;
    }

    /**
     * @return TileMatrixSet[]
     */
    public function getMatrixSets()
    {
        $identifiers = array();
        foreach ($this->getTilematrixSetlinks() as $tmsl) {
            $identifiers[] = $tmsl->getTileMatrixSet();
        }
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('identifier', $identifiers))
        ;
        return $this->getSource()->getTilematrixsets()->matching($criteria)->getValues();
    }

    public function getSupportedCrsNames()
    {
        $names = array();
        foreach ($this->getMatrixSets() as $matrixSet) {
            $names[] = ConfigGeneratorCommon::urnToSrsCode($matrixSet->getSupportedCrs());
        }
        return $names;
    }

    /**
     * @param UrlTemplateType[] $resourceUrls
     * @return $this
     */
    public function setResourceUrl(array $resourceUrls = array())
    {
        $this->resourceUrl = $resourceUrls;
        return $this;
    }

    /**
     * @param UrlTemplateType $resourceUrl
     * @return $this
     */
    public function addResourceUrl(UrlTemplateType $resourceUrl)
    {
        $this->resourceUrl[] = $resourceUrl;
        return $this;
    }

    /**
     * @return UrlTemplateType[]
     */
    public function getResourceUrl()
    {
        $result = [];
        foreach ($this->resourceUrl ?? [] as $item) {
            if ($item instanceof UrlTemplateType) {
                $result[] = $item;
            } elseif (is_array($item)) {
                $ru = new UrlTemplateType();
                $ru->setFormat($item['format'] ?? null);
                $ru->setResourceType($item['resourceType'] ?? null);
                $ru->setTemplate($item['template'] ?? null);
                $ru->setExtension($item['extension'] ?? null);
                $result[] = $ru;
            }
        }
        return $result;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }

    public function getSublayer()
    {
        return $this->sublayer;
    }


    /**
     * @return UrlTemplateType[]
     */
    public function getTileResources()
    {
        $matches = array();
        foreach ($this->getResourceUrl() as $ru) {
            if ($ru->getResourceType() === 'tile') {
                $matches[] = $ru;
            }
        }
        return $matches;
    }

    /**
     * @return string[]
     */
    public function getUniqueTileFormats()
    {
        $formats = array();
        foreach ($this->getTileResources() as $resourceUrl) {
            $formats[] = $resourceUrl->getFormat();
        }
        return array_unique($formats);
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        $newResourceUrls = array();
        foreach ($this->getResourceUrl() as $resourceUrl) {
            $resourceUrl->mutateUrls($transformer);
            $newResourceUrls[] = clone $resourceUrl;
        }
        $this->setResourceUrl($newResourceUrls);
    }
}
