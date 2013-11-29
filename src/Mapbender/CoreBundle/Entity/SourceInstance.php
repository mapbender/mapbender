<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\Signer;

/**
 * @author Karim Malhas
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_sourceinstance")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * ORM\DiscriminatorMap({"mb_core_sourceinstance" = "SourceInstance"})
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
     * @var string $title The source title
     * @ORM\Column(type="string", nullable=true)
     */
    protected $title;

    /**
     * @ORM\ManyToOne(targetEntity="Layerset", inversedBy="instances", cascade={"persist","refresh"})
     * @ORM\JoinColumn(name="layerset", referencedColumnName="id")
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
     * Creates an instance
     */
    public function __construct()
    {

    }

    /**
     * Returns an id
     *
     * @return integer id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns a title
     *
     * @param String title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets a title
     *
     * @param String $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns a source type
     *
     * @return String type
     */
    public abstract function getType();

    /**
     * Returns a manager type
     *
     * @return String a manager type
     */
    public abstract function getManagertype();

    /**
     * Returns a full class name
     *
     * @return string
     */
    public function getClassname()
    {
        return get_class();
    }

    /**
     * Returns assets
     *
     * @return array assets
     */
    public function getAssets()
    {
        return array();
    }

    /**
     * Sets a weight
     *
     * @param integer $weight
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
     * Sets the layerset
     *
     * @param Layerset $layerset Layerset
     * @return Sourceinstance
     */
    public function setLayerset($layerset)
    {
        $this->layerset = $layerset;
        return $this;
    }

    /**
     * Returns the layerset
     * @return Layerset
     */
    public function getLayerset()
    {
        $this->layerset;
    }

    /**
     * Sets an enabled
     *
     * @param integer $enabled
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
     * Returns instance source
     *
     * @return Source
     */
    public abstract function getSource();

    /**
     * Sets an id
     * @param integer $id id
     */
    public abstract function setId($id);

    /**
     * Sets a configuration of a source instance
     *
     * @param array $configuration configuration of a source instance
     */
    public abstract function setConfiguration($configuration);

    /**
     *  Returns a configuration of a source instance
     *
     *  @param   Signer  $signer  String signer for URL protection
     */
    public abstract function getConfiguration(Signer $signer=null);

    /**
     * Remove a source instance from a database
     * @param EntityManager $em
     */
    public abstract function remove(EntityManager $em);

    /**
     * Copies a source instance
     * @param EntityManager $em
     */
    public abstract function copy(EntityManager $em);
}
