<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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
    /**
     * @var integer $id
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var string $title The source title
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $title;

    /**
     * @var string $alias The source alias
     */
    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    protected $alias = "";

    /**
     * @var string $description The source description
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected $description;

    #[ORM\Column(type: 'string', nullable: true)]
    protected $type;

    public function __construct()
    {
    }


    /**
     * @return ArrayCollection|SourceInstance[]
     */
    abstract public function getInstances(): ArrayCollection|array;

    /**
     * @return ArrayCollection|SourceItem[]
     */
    abstract public function getLayers();

    /**
     * Set id
     * @param integer $id source id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param  string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param  string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set alias
     *
     * @param  string $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
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
