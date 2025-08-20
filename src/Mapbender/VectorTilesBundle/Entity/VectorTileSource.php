<?php

namespace Mapbender\VectorTilesBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\VectorTilesBundle\VectorTilesDataSource;

#[ORM\Entity]
#[ORM\Table(name: 'mb_vectortiles_source')]
class VectorTileSource extends Source
{

    public function __construct()
    {
        parent::__construct();
        $this->setType(VectorTilesDataSource::TYPE);
        $this->instances = new ArrayCollection();
    }

    #[ORM\Column(name: 'json_url', type: 'string', nullable: true)]
    private ?string $jsonUrl = null;

    #[ORM\Column(name: 'bounds', type: 'string', nullable: true)]
    private ?string $bbox = null;

    #[ORM\Column(name: 'referer', type: 'string', nullable: true)]
    private ?string $referer = null;

    #[ORM\Column(name: 'min_zoom', type: 'integer', nullable: true)]
    private ?int $minZoom = null;

    #[ORM\Column(name: 'max_zoom', type: 'integer', nullable: true)]
    private ?int $maxZoom = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $version = "";

    #[ORM\OneToMany(mappedBy: 'source', targetEntity: VectorTileInstance::class, cascade: ['remove'])]
    protected $instances;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $metadata = null;

    public function getJsonUrl(): ?string
    {
        return $this->jsonUrl;
    }

    public function setJsonUrl(?string $jsonUrl): void
    {
        $this->jsonUrl = $jsonUrl;
    }

    public function getReferer(): ?string
    {
        return $this->referer;
    }

    public function setReferer(?string $referer): void
    {
        $this->referer = $referer;
    }

    public function getMinZoom(): ?int
    {
        return $this->minZoom;
    }

    public function setMinZoom(?int $minZoom): void
    {
        $this->minZoom = $minZoom;
    }

    public function getMaxZoom(): ?int
    {
        return $this->maxZoom;
    }

    public function setMaxZoom(?int $maxZoom): void
    {
        $this->maxZoom = $maxZoom;
    }


    public function getLayers(): array|Collection
    {
        return [];
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getBbox(): ?string
    {
        return $this->bbox;
    }

    public function getBoundsArray(): ?array
    {
        if (!$this->bbox) {
            return null;
        }
        return json_decode($this->bbox, true);
    }

    public function setBbox(string|array|null $bbox): void
    {
        if (is_array($bbox)) {
            $bbox = json_encode($bbox);
        }
        $this->bbox = $bbox;
    }

    public function getMetadata(): ?string
    {
        return $this->metadata;
    }

    public function getMetadataArray(): ?array
    {
        if (!$this->metadata) {
            return [];
        }

        $metadataArray = json_decode($this->metadata, true) ?? [];
        foreach ($metadataArray as $key => $value) {
            if (is_bool($value)) {
                $metadataArray[$key] = $value ? 'true' : 'false';
            }
        }
        return $metadataArray;
    }

    public function setMetadata(?string $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * @return Collection|VectorTileInstance[]
     */
    public function getInstances(): Collection|array
    {
        return $this->instances;
    }

    public function getDisplayUrl(): ?string
    {
        return $this->getJsonUrl();
    }


}
