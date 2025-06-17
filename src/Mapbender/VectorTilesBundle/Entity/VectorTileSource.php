<?php

namespace Mapbender\VectorTilesBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Entity\Source;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'mb_vectortiles_source')]
class VectorTileSource extends Source
{

    #[ORM\Column(name: 'json_url', type: 'string', nullable: true)]
    private ?string $jsonUrl = null;

    public function getJsonUrl(): ?string
    {
        return $this->jsonUrl;
    }

    public function setJsonUrl(?string $jsonUrl): void
    {
        $this->jsonUrl = $jsonUrl;
    }


    public function getInstances(): array|ArrayCollection
    {
        return [];
    }

    public function getLayers()
    {
        return [];
    }

    public function getViewTemplate($frontend = false)
    {

    }
}
