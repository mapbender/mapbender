<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\Source\DataSource;
use Mapbender\CoreBundle\Component\Source\MutableHttpOriginInterface;

/**
 * Source entity
 *
 * @author Paul Schmidt
 */
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
// Discriminator map is filled dynamically by @see SourceMetadataListener::loadClassMetadata. However, it can't be
// empty initially, because otherwise Doctrine will try to identify all classes inheriting from Source including
// MappedSuperclasses, which does not work.
#[ORM\DiscriminatorMap(['wmssource' => '\Mapbender\WmsBundle\Entity\WmsSource'])]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string', length: 15)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'mb_core_source')]
abstract class Source
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected null|int|string $id = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    protected ?string $alias = "";

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $type = null;

    public function __construct()
    {
    }


    /**
     * @return Collection|SourceInstance[]
     */
    abstract public function getInstances(): Collection|array;

    /**
     * @return Collection|SourceItem[]
     */
    abstract public function getLayers(): Collection|array;

    public function setId(int|string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Returns a Source as String
     *
     * @return String Source as String
     */
    public function __toString()
    {
        return (string) $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type Set type. Should be a return value of DataSource::getName()
     * @see DataSource::getName()
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }


}
