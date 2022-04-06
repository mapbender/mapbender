<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\SourceMetadata;

/**
 * @author Karim Malhas
 * @author Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 *
 * @ORM\Entity(repositoryClass="Mapbender\CoreBundle\Entity\Repository\SourceInstanceRepository")
 * @ORM\Table(name="mb_core_sourceinstance")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
abstract class SourceInstance extends SourceInstanceAssignment
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $title
     * @ORM\Column(type="string", nullable=true)
     */
    protected $title;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $basesource = false;

    /**
     * @var ReusableSourceInstanceAssignment[]|Collection
     * @ORM\OneToMany(targetEntity="ReusableSourceInstanceAssignment", mappedBy="instance", orphanRemoval=true, cascade={"remove"})
     */
    protected $reusableassignments;

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
    public function setLayerset(Layerset $layerset=null)
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
    abstract public function getDisplayTitle();

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
