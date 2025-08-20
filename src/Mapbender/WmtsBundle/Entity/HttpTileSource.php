<?php


namespace Mapbender\WmtsBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\HttpParsedSource;
use Mapbender\CoreBundle\Entity\Keyword;
use Mapbender\WmtsBundle\TmsDataSource;
use Mapbender\WmtsBundle\WmtsDataSource;


/** Contains only fields and methods common to both Wmts and TMS **/
#[ORM\MappedSuperclass]
abstract class HttpTileSource extends HttpParsedSource
    implements MutableUrlTarget, ContainingKeyword
{
    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $version = "";

    /**
     * @var WmtsLayerSource[]|ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'source', targetEntity: WmtsLayerSource::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['id' => 'asc'])]
    protected $layers;

    /**
     * @var WmtsInstance[]|ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'source', targetEntity: WmtsInstance::class, cascade: ['remove'])]
    protected $instances;

    /**
     * @var ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'source', targetEntity: TileMatrixSet::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['id' => 'asc'])]
    protected $tilematrixsets;

    /**
     * @var Contact
     */
    #[ORM\OneToOne(targetEntity: Contact::class, cascade: ['persist', 'remove'])]
    protected $contact;

    /**
     * @var WmtsSourceKeyword[]|ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'reference', targetEntity: WmtsSourceKeyword::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['value' => 'asc'])]
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
        $source = new WmtsSource();
        $source->setType(TmsDataSource::TYPE);
        return $source;
    }

    /**
     * @return WmtsSource
     */
    public static function wmtsFactory()
    {
        $source = new WmtsSource();
        $source->setType(WmtsDataSource::TYPE);
        return $source;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return WmtsLayerSource[]|Collection
     */
    public function getLayers(): array|Collection
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
     * @return Collection|WmtsInstance[]
     */
    public function getInstances(): Collection|array
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
     * @param Collection $keywords
     */
    public function setKeywords(Collection $keywords)
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
     * @param Collection $tilematrixsets
     */
    public function setTilematrixsets(Collection $tilematrixsets)
    {
        $this->tilematrixsets = $tilematrixsets;
    }

    /**
     * @return TileMatrixSet[]|Collection
     */
    public function getTilematrixsets(): array|Collection
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
