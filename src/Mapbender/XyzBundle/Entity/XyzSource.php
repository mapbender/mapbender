<?php

namespace Mapbender\XyzBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\XyzBundle\XyzDataSource;

#[ORM\Entity]
#[ORM\Table(name: 'mb_xyz_source')]
class XyzSource extends Source
{

    public function __construct()
    {
        parent::__construct();
        $this->setType(XyzDataSource::TYPE);
        $this->instances = new ArrayCollection();
    }

    #[ORM\Column(name: 'url_template', type: 'string', nullable: false)]
    private string $urlTemplate = '';

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $attribution = null;

    #[ORM\OneToMany(mappedBy: 'source', targetEntity: XyzInstance::class, cascade: ['remove'])]
    protected $instances;

    public function getUrlTemplate(): string
    {
        return $this->urlTemplate;
    }

    public function setUrlTemplate(string $urlTemplate): void
    {
        $this->urlTemplate = $urlTemplate;
    }

    public function getAttribution(): ?string
    {
        return $this->attribution;
    }

    public function setAttribution(?string $attribution): void
    {
        $this->attribution = $attribution;
    }

    public function getLayers(): array|Collection
    {
        return [];
    }

    /**
     * @return Collection|XyzInstance[]
     */
    public function getInstances(): Collection|array
    {
        return $this->instances;
    }

    public function getDisplayUrl(): ?string
    {
        return $this->getUrlTemplate();
    }
}
