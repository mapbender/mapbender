<?php

namespace Mapbender\OgcApiFeaturesBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\Style;
use Mapbender\OgcApiFeaturesBundle\OgcApiFeaturesDataSource;

#[ORM\Entity]
#[ORM\Table(name: 'mb_ogc_api_features_source')]
class OgcApiFeaturesSource extends Source
{
    #[ORM\OneToMany(mappedBy: 'source', targetEntity: OgcApiFeaturesLayerSource::class, cascade: ['persist', 'remove'])]
    protected Collection $layers;

    #[ORM\OneToMany(mappedBy: 'source', targetEntity: OgcApiFeaturesInstance::class, cascade: ['remove'])]
    protected Collection $instances;

    #[ORM\Column(name: 'json_url', type: 'string', nullable: true)]
    private ?string $jsonUrl = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $version = "";

    #[ORM\Column(name: 'attribution', type: 'string', nullable: true)]
    private ?string $attribution = null;

    protected bool $activateNewLayers = true;
    protected bool $selectNewLayers = true;

    public function __construct()
    {
        parent::__construct();
        $this->setType(OgcApiFeaturesDataSource::TYPE);
        $this->instances = new ArrayCollection();
        $this->layers = new ArrayCollection();
    }

    public function getJsonUrl(): ?string
    {
        return $this->jsonUrl;
    }

    public function setJsonUrl(?string $jsonUrl): void
    {
        $this->jsonUrl = $jsonUrl;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }

    public function getAttribution(): ?string
    {
        return $this->attribution;
    }

    public function setAttribution(?string $attribution): void
    {
        $this->attribution = $attribution;
    }

    public function getInstances(): Collection|array
    {
        return $this->instances;
    }

    public function getDisplayUrl(): ?string
    {
        return $this->jsonUrl;
    }

    public function setLayers(ArrayCollection $layers): void
    {
        $this->layers = $layers;
    }

    public function getLayers(): Collection|array
    {
        return $this->layers;
    }

    public function addLayer(OgcApiFeaturesLayerSource $layer): void
    {
        $layer->setSource($this);
        $this->layers->add($layer);
    }

    public function activateNewLayers(): bool
    {
        return $this->activateNewLayers;
    }

    public function selectNewLayers(): bool
    {
        return $this->selectNewLayers;
    }


    public function setActivateNewLayers(bool $activateNewLayers): self
    {
        $this->activateNewLayers = $activateNewLayers;
        return $this;
    }

    public function setSelectNewLayers(bool $selectNewLayers): self
    {
        $this->selectNewLayers = $selectNewLayers;
        return $this;
    }

    private ?array $styleMap = null;

    public function getStyleMap(EntityManagerInterface $entityManager): array
    {
        if (!$this->styleMap) {
            $styles = $entityManager->getRepository(Style::class)->findBy([
                'sourceId' => $this->id,
            ]);
            $this->styleMap = [];
            foreach ($styles as $style) {
                $cid = $style->getCollectionId();
                if ($cid && !isset($this->styleMap[$cid])) {
                    $this->styleMap[$cid] = $style->getId();
                }
            }
        }
        return $this->styleMap;
    }


}
