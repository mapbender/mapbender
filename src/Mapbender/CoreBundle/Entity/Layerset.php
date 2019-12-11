<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Layerset configuration entity
 *
 * @author Christian Wygoda
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_layerset", uniqueConstraints={@UniqueConstraint(name="layerset_idx", columns={"application_id", "title"})})
 * @UniqueEntity(fields={"application", "title"}, message ="Duplicate entry for key 'title'.")
 */
class Layerset
{

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $title The layerset title
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @var Application The configuration entity for the application
     * @ORM\ManyToOne(targetEntity="Application", inversedBy="layersets")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $application;

    /**
     * @ORM\OneToMany(targetEntity="SourceInstance", mappedBy="layerset", cascade={"remove", "persist"})
     * @ORM\JoinColumn(name="instances", referencedColumnName="id")
     * @ORM\OrderBy({"weight" = "asc"})
     */
    protected $instances;

    /**
     * Reusable source instances: separate relation (via join table) to instances NOT owned by this Layerset
     * @var SourceInstance[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="SourceInstance", cascade={"remove"}, orphanRemoval=true)
     * @ORM\JoinTable(name="mb_core_layersets_sourceinstances",
     *      joinColumns={@ORM\JoinColumn(name="layerset_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="instance_id", referencedColumnName="id", onDelete="CASCADE")}
     *     )
     */
    protected $unownedInstances;

    /**
     * Layerset constructor.
     */
    public function __construct()
    {
        $this->instances = new ArrayCollection();
        $this->unownedInstances = new ArrayCollection();
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
     * @param Collection $instances Collection of the SourceInstances
     * @return $this
     */
    public function setInstances($instances)
    {
        $this->instances = $instances;

        return $this;
    }

    /**
     * Get instances
     *
     * @param bool $includeUnowned NOTE: cannot be true by default to avoid erroneous doctrine behaviour
     * @return \Mapbender\WmsBundle\Entity\WmsInstance[]|SourceInstance[]|Collection
     */
    public function getInstances($includeUnowned = false)
    {
        if ($includeUnowned) {
            // @todo reusable source instances: find common weight sorting strategy for owned vs unowned instances
            $owned = $this->instances->getValues();
            $unowned = $this->getUnownedInstances()->getValues();
            $combinedInstances = new ArrayCollection(array_merge($owned, $unowned));
            return $combinedInstances;
        }
        return $this->instances;
    }

    /**
     * Read-only informative pseudo-relation
     *
     * @param Source $source
     * @return ArrayCollection|Collection
     */
    public function getInstancesOf(Source $source)
    {
        return $this->instances->filter(function ($instance) use ($source) {
            /** @var SourceInstance $instance */
            return $instance->getSource() === $source;
        });
    }

    /**
     * @return ArrayCollection|SourceInstance[]
     */
    public function getUnownedInstances()
    {
        return $this->unownedInstances;
    }

    public function addUnownedInstance(SourceInstance $instance)
    {
        if ($instance->getLayerset()) {
            throw new \LogicException("Instance with assigned layerset cannot be added as unowned");
        }
        $this->unownedInstances->add($instance);
    }

    public function removeUnownedInstance(SourceInstance $instance)
    {
        $this->unownedInstances->removeElement($instance);
    }

    /**
     * @return string Layerset ID
     */
    public function __toString()
    {
        return (string) $this->getId();
    }
}
