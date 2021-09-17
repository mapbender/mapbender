<?php


namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="mb_core_layersets_sourceinstances")
 */
class ReusableSourceInstanceAssignment extends SourceInstanceAssignment
{
    /**
     * @var integer|null
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var SourceInstance
     * @ORM\ManyToOne(targetEntity="SourceInstance", inversedBy="reusableassignments")
     * @ORM\JoinColumn(name="instance_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $instance;

    /**
     * @var Layerset
     * @ORM\ManyToOne(targetEntity="Layerset", inversedBy="reusableInstanceAssignments", cascade={"refresh"}))
     * @ORM\JoinColumn(name="layerset_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $layerset;

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
}

