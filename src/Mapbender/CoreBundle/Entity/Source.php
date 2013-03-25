<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;

/**
 * Source entity
 *
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_source")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * ORM\DiscriminatorMap({"mb_core_source" = "Source"})
 */
abstract class Source
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
     * @var string $alias The source alias
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    protected $alias = "";

    /**
     * @var string $description The source description
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

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
     * @return Source
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
     * @param string $description
     * @return Source
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
     * @param string $alias
     * @return Source
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
     * Get full class name
     *
     * @return string
     */
    public function getClassname()
    {
        return get_class();
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
     * Creates a SourceInstance
     */
    public abstract function createInstance();

    /**
     * Remove a source from a database
     */
    public abstract function remove(EntityManager $em);
}
