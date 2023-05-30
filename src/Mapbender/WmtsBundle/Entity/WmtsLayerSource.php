<?php

namespace Mapbender\WmtsBundle\Entity;

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
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_wmtslayersource")
 *
 * @property WmtsSource $source
 * @method WmtsSource getSource
 */
class WmtsLayerSource extends SourceItem implements MutableUrlTarget
{

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
    protected $source;

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    protected $latlonBounds;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $boundingBoxes;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $styles;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $infoformats;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $tilematrixSetlinks;

    /**
     * @var UrlTemplateType[]
     * @ORM\Column(type="array", nullable=true)
     */
    protected $resourceUrl;

    public function __construct()
    {
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
    public function setLatlonBounds(BoundingBox $latlonBounds = NULL)
    {
        $this->latlonBounds = $latlonBounds;
        return $this;
    }

    /**
     * @return BoundingBox
     */
    public function getLatlonBounds()
    {
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
        return $this->boundingBoxes;
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
        return $this->styles;
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
        return $this->tilematrixSetlinks;
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
        return $this->resourceUrl;
    }

    /**
     * @return UrlTemplateType[]
     */
    public function getTileResources()
    {
        $matches = array();
        foreach ($this->resourceUrl as $ru) {
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
