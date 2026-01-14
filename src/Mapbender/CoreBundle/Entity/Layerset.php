<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Layerset configuration entity
 *
 * @author Christian Wygoda
 */
#[UniqueEntity(fields: ['application', 'title'], message: 'mb.core.layerset.unique_title')]
#[ORM\Entity(repositoryClass: \Mapbender\CoreBundle\Entity\Repository\LayersetRepository::class)]
#[ORM\Table(name: 'mb_core_layerset')]
#[UniqueConstraint(name: 'layerset_idx', columns: ['application_id', 'title'])]
class Layerset
{

    /**
     * @var integer $id
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var string $title The layerset title
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 128)]
    protected $title;

    /**
     * @var Application The configuration entity for the application
     */
    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'layersets')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected $application;

    /**
     * NOTE: must specify portable default to avoid a nullable troolean column
     */
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    protected $selected = true;

    #[ORM\OneToMany(mappedBy: 'layerset', targetEntity: SourceInstance::class, cascade: ['remove', 'persist'])]
    #[ORM\JoinColumn(name: 'instances', referencedColumnName: 'id')]
    #[ORM\OrderBy(['weight' => 'asc'])]
    protected $instances;

    /**
     * Reusable source instance assignments
     * Relation to actual SourceInstance goes via separate assignment entity that stores sorting weight and enabled state
     * @var ReusableSourceInstanceAssignment[]|ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'layerset', targetEntity: ReusableSourceInstanceAssignment::class, cascade: ['remove', 'persist'])]
    #[ORM\OrderBy(['weight' => 'ASC'])]
    protected $reusableInstanceAssignments;

    /**
     * Layerset constructor.
     */
    public function __construct()
    {
        $this->instances = new ArrayCollection();
        $this->reusableInstanceAssignments = new ArrayCollection();
    }

    /**
     * Set id.
     *
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        if (null !== $id) {
            $this->id = $id;
        }

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
     * @param string $title
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
     * Set application
     *
     * @param Application $application
     * @return $this
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * Get application
     *
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return bool
     */
    public function getSelected()
    {
        return !!$this->selected;
    }

    /**
     * @param bool $value
     */
    public function setSelected($value)
    {
        return $this->selected = !!$value;
    }

    /**
     * Add SourceInstance
     *
     * @param SourceInstance $instance
     */
    public function addInstance(SourceInstance $instance)
    {
        $this->instances->add($instance);
    }

    /**
     * Set instances
     *
     * @param Collection $instances
     * @return $this
     */
    public function setInstances($instances)
    {
        $this->instances = $instances;

        return $this;
    }

    /**
     * @return ReusableSourceInstanceAssignment[]|ArrayCollection
     */
    public function getReusableInstanceAssignments()
    {
        return $this->reusableInstanceAssignments;
    }

    /**
     * @param ReusableSourceInstanceAssignment[]|ArrayCollection $reusableInstanceAssignments
     * @return $this
     */
    public function setReusableInstanceAssignments($reusableInstanceAssignments)
    {
        $this->reusableInstanceAssignments = $reusableInstanceAssignments;
        return $this;
    }

    /**
     * Get instances
     *
     * @param bool $includeUnowned NOTE: cannot be true by default to avoid erroneous doctrine behaviour
     * @return SourceInstance[]|Collection
     */
    public function getInstances($includeUnowned = false)
    {
        if ($includeUnowned) {
            return $this->getCombinedInstances();
        } else {
            return $this->instances;
        }
    }

    /**
     * Get BOTH owned instances and assigned reusable instances in
     * a single collection.
     *
     * @return SourceInstance[]|ArrayCollection
     */
    public function getCombinedInstances()
    {
        return $this->getCombinedInstanceAssignments()->map(function ($assignment) {
            /** @var SourceInstanceAssignment $assignment */
            return $assignment->getInstance();
        });
    }

    /**
     * Returns a list of source instance assignments, both directly owned and reusable.
     *
     * @return SourceInstanceAssignment[]|ArrayCollection
     */
    public function getCombinedInstanceAssignments(): Collection|array
    {
        $owned = $this->instances->getValues();
        $unowned = $this->getReusableInstanceAssignments()->getValues();
        $combined = new ArrayCollection(array_merge($owned, $unowned));
        return $combined->matching(Criteria::create()->orderBy(array(
            'weight' => Order::Ascending
        )));
    }

    /**
     * Read-only informative pseudo-relation
     *
     * @param Source $source
     * @param bool $includeUnowned
     * @return SourceInstance[]|ArrayCollection
     */
    public function getInstancesOf(Source $source, $includeUnowned = true)
    {
        return $this->getInstances($includeUnowned)->filter(function ($instance) use ($source) {
            /** @var SourceInstance $instance */
            return $instance->getSource() === $source;
        });
    }

    /**
     * @return ArrayCollection|SourceInstance[]
     */
    public function getAssignedReusableInstances()
    {
        return $this->getReusableInstanceAssignments()->map(function($assignment) {
            /** @var ReusableSourceInstanceAssignment $assignment */
            return $assignment->getInstance();
        });
    }

    /**
     * @return string Layerset ID
     */
    public function __toString()
    {
        return (string) $this->getId();
    }
}
