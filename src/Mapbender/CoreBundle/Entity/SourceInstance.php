<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\SourceMetadata;

/**
 * @author Karim Malhas
 * @author Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_sourceinstance")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
abstract class SourceInstance
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
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $enabled = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $basesource = false;

    /**
     * @var Source
     */
    protected $source;

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
        return $this->source->getType();
    }

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
     * Returns a weight
     *
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
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
     * Sets an enabled
     *
     * @param  integer        $enabled
     * @return SourceInstance SourceInstance
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Returns an enabled
     *
     * @return integer
     */
    public function getEnabled()
    {
        return $this->enabled;
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
     * @return SourceInstanceItem[]|Collection
     */
    abstract public function getLayers();

    /**
     *
     * @return SourceMetadata|null
     * @deprecated metadata rendering is doable and should be done purely in twig
     * @see \Mapbender\CoreBundle\Controller\ApplicationController::metadataAction()
     */
    abstract public function getMetadata();

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }

    /**
     * @return bool
     * @deprecated remove on master branch; legacy attribute with no discernable purpose
     *      Client-side usages should access options.treeOptions.selected on the source instance's root layer instead.
     */
    final public function getVisible()
    {
        return true;
    }
}
