<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOM\UserBundle\Security\Permission\YamlDefinedPermissionEntity;
use Mapbender\CoreBundle\Entity\Repository\SourceInstanceRepository;

/**
 * @see SupportsProxy
 */
#[ORM\Entity(repositoryClass: SourceInstanceRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\Table(name: 'mb_core_sourceinstance')]
abstract class SourceInstance extends SourceInstanceAssignment implements YamlDefinedPermissionEntity
{
    /**
     * @var integer $id
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected bool $basesource = false;

    /**
     * @var ReusableSourceInstanceAssignment[]|Collection
     */
    #[ORM\OneToMany(mappedBy: 'instance', targetEntity: ReusableSourceInstanceAssignment::class, cascade: ['remove'], orphanRemoval: true)]
    protected $reusableassignments;


    /** @var string[]|null */
    protected ?array $yamlRoles = null;

    final public function getInstance()
    {
        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param String $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Returns a source type
     *
     * @return String type
     */
    public function getType()
    {
        return $this->getSource()->getType();
    }

    /**
     * @param Layerset|null $layerset
     * @return $this
     */
    public function setLayerset(?Layerset $layerset = null)
    {
        $this->layerset = $layerset;
        return $this;
    }

    /**
     * @return Layerset|null
     */
    public function getLayerset()
    {
        return $this->layerset;
    }

    /**
     * Sets base source
     *
     * @param  boolean $baseSource
     * @return $this
     */
    public function setBasesource($baseSource)
    {
        $this->basesource = $baseSource;

        return $this;
    }

    /**
     * Returns a basesource
     *
     * @return bool
     */
    public function isBasesource()
    {
        return $this->basesource;
    }

    /**
     * @return string[]|null
     */
    public function getYamlRoles(): ?array
    {
        return $this->yamlRoles;
    }

    /**
     * @param string[]|null $yamlRoles
     */
    public function setYamlRoles(?array $yamlRoles): void
    {
        $this->yamlRoles = $yamlRoles;
    }

    /**
     * Sets source
     *
     * @param Source $source Source object
     * @return SourceInstance
     */
    abstract public function setSource($source);

    /**
     * Returns source
     *
     * @return Source
     */
    abstract public function getSource();

    /**
     * @return SourceInstanceItem[]|ArrayCollection
     */
    abstract public function getLayers();

    /**
     * @return string
     */
    abstract public function getDisplayTitle(): string;



    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
