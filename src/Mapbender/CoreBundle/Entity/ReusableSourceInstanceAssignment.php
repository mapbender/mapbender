<?php


namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\Component\Collections\WeightSortedCollectionMember;

/**
 * @see SourceInstanceAssignment; cannot extend though, because property layerset cannot be redeclared
 */
#[ORM\Entity]
#[ORM\Table(name: 'mb_core_layersets_sourceinstances')]
class ReusableSourceInstanceAssignment implements WeightSortedCollectionMember
{
    /**
     * @var integer|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected $id;

    /**
     * @var SourceInstance
     */
    #[ORM\ManyToOne(targetEntity: SourceInstance::class, inversedBy: 'reusableassignments')]
    #[ORM\JoinColumn(name: 'instance_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $instance;

    /**
     * @var Layerset
     */
    #[ORM\ManyToOne(targetEntity: Layerset::class, cascade: ['refresh'], inversedBy: 'reusableInstanceAssignments')]
    #[ORM\JoinColumn(name: 'layerset_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $layerset;

    #[ORM\Column(type: 'integer')]
    protected int $weight = 0;

    #[ORM\Column(type: 'boolean')]
    protected bool $enabled = true;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return $this;
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return SourceInstance
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param SourceInstance $instance
     * @return $this
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
        return $this;
    }

    /**
     * @return Layerset
     */
    public function getLayerset()
    {
        return $this->layerset;
    }

    /**
     * @param Layerset $layerset
     * @return $this
     */
    public function setLayerset($layerset)
    {
        $this->layerset = $layerset;
        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight($weight): void
    {
        $this->weight = $weight;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

}

