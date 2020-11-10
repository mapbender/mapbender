<?php


namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\Component\Collections\WeightSortedCollectionMember;

/**
 * @ORM\MappedSuperclass()
 */
abstract class SourceInstanceAssignment implements WeightSortedCollectionMember
{
    /**
     * @var Layerset|null
     * @ORM\ManyToOne(targetEntity="Layerset", inversedBy="instances", cascade={"refresh"})
     * @ORM\JoinColumn(name="layerset", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $layerset;

    /**
     * @var integer $weight The sorting weight for display
     * @ORM\Column(type="integer")
     */
    protected $weight;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $enabled = true;

    /**
     * @return SourceInstance
     */
    abstract public function getInstance();

    /**
     * Sets a weight
     *
     * @param integer $weight
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Returns sorting weight (within layerset)
     *
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = !!$enabled;
        return $this;
    }
}
