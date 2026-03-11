<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'mb_styles')]
class Style
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $style = null;

    #[ORM\Column(name: 'source_type', type: 'string', length: 255, nullable: true)]
    private ?string $sourceType = null;

    #[ORM\Column(name: 'source_id', type: 'integer', nullable: true)]
    private ?int $sourceId = null;

    #[ORM\Column(name: 'collection_id', type: 'string', length: 255, nullable: true)]
    private ?string $collectionId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getStyle(): ?string
    {
        return $this->style;
    }

    public function setStyle(?string $style): self
    {
        $this->style = $style;
        return $this;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(?string $sourceType): self
    {
        $this->sourceType = $sourceType;
        return $this;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function setSourceId(?int $sourceId): self
    {
        $this->sourceId = $sourceId;
        return $this;
    }

    public function getCollectionId(): ?string
    {
        return $this->collectionId;
    }

    public function setCollectionId(?string $collectionId): self
    {
        $this->collectionId = $collectionId;
        return $this;
    }

    public function isMultiLayer(): bool
    {
        if (!$this->style) {
            return false;
        }
        $data = \json_decode($this->style, true);
        return \is_array($data) && isset($data['version']) && \is_array($data['layers'] ?? null);
    }

    public function getLayerCount(): int
    {
        if (!$this->style) {
            return 0;
        }
        $data = \json_decode($this->style, true);
        if (\is_array($data) && \is_array($data['layers'] ?? null)) {
            return \count($data['layers']);
        }
        return 0;
    }
}
