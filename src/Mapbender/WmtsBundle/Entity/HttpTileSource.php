<?php


namespace Mapbender\WmtsBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\HttpParsedSource;
use Mapbender\CoreBundle\Entity\Keyword;


/**
 * @ORM\MappedSuperclass
 * Contains only fields and methods common to both Wmts and TMS
 */
abstract class HttpTileSource extends HttpParsedSource
    implements MutableUrlTarget, ContainingKeyword
{
    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    protected $version = "";

    /**
     * @var WmtsLayerSource[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="WmtsLayerSource",mappedBy="source", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $layers;

    /**
     * @var WmtsInstance[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="WmtsInstance",mappedBy="source", cascade={"remove"})
     */
    protected $instances;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="TileMatrixSet",mappedBy="source", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $tilematrixsets;

    /**
     * @var Contact
     * @ORM\OneToOne(targetEntity="Mapbender\CoreBundle\Entity\Contact", cascade={"persist", "remove"})
     */
    protected $contact;

    /**
     * @var WmtsSourceKeyword[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="WmtsSourceKeyword",mappedBy="reference", cascade={"persist", "remove"})
     * @ORM\OrderBy({"value" = "asc"})
     */
    protected $keywords;

    public function __construct()
    {
        parent::__construct();
        $this->layers = new ArrayCollection();
        $this->instances = new ArrayCollection();
        $this->tilematrixsets = new ArrayCollection();
        $this->contact = new Contact();
        $this->keywords = new ArrayCollection();
    }

    /**
     * @return static
     */
    public static function tmsFactory()
    {
        // HACK: no distinct class for TMS
        $source = new WmtsSource();
        $source->setType($source::TYPE_TMS);
        return $source;
    }

    /**
     * @return WmtsSource
     */
    public static function wmtsFactory()
    {
        // HACK: no distinct class for Wmts
        $source = new WmtsSource();
        $source->setType($source::TYPE_WMTS);
        return $source;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return WmtsLayerSource[]|ArrayCollection
     */
    public function getLayers()
    {
        return $this->layers;
    }

    /**
     * @param WmtsLayerSource[]|ArrayCollection $layers
     */
    public function setLayers($layers)
    {
        $this->layers = $layers;
    }

    /**
     * @param WmtsLayerSource $layer
     */
    public function addLayer(WmtsLayerSource $layer)
    {
        $this->layers->add($layer);
        $layer->setSource($this);
    }

    /**
     * @return ArrayCollection|WmtsInstance[]
     */
    public function getInstances()
    {
        return $this->instances;
    }

    /**
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param Contact $contact
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * @return ArrayCollection
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param ArrayCollection $keywords
     */
    public function setKeywords(ArrayCollection $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * @param Keyword $keyword
     */
    public function addKeyword(Keyword $keyword)
    {
        $this->keywords->add($keyword);
    }

    /**
     * @param ArrayCollection $tilematrixsets
     */
    public function setTilematrixsets(ArrayCollection $tilematrixsets)
    {
        $this->tilematrixsets = $tilematrixsets;
    }

    /**
     * @return TileMatrixSet[]|ArrayCollection
     */
    public function getTilematrixsets()
    {
        return $this->tilematrixsets;
    }

    /**
     * @param TileMatrixSet $tilematrixset
     */
    public function addTilematrixset(TileMatrixSet $tilematrixset)
    {
        $this->tilematrixsets->add($tilematrixset);
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        $this->setOriginUrl($transformer->process($this->getOriginUrl()));
        foreach ($this->getLayers() as $layer) {
            $layer->mutateUrls($transformer);
        }
    }
}
