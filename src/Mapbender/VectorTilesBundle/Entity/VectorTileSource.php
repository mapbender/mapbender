<?php

namespace Mapbender\VectorTilesBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Mapbender\CoreBundle\Entity\Source;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\VectorTilesBundle\VectorTilesDataSource;

#[ORM\Entity]
#[ORM\Table(name: 'mb_vectortiles_source')]
class VectorTileSource extends Source
{

    public function __construct()
    {
        parent::__construct();
        $this->setType(VectorTilesDataSource::TYPE);
    }

    #[ORM\Column(name: 'json_url', type: 'string', nullable: true)]
    private ?string $jsonUrl = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $version = "";

    public function getJsonUrl(): ?string
    {
        return $this->jsonUrl;
    }

    public function setJsonUrl(?string $jsonUrl): void
    {
        $this->jsonUrl = $jsonUrl;
    }


    public function getInstances(): array|Collection
    {
        return [];
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


}
