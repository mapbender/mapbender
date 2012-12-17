<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Mapbender\CoreBundle\Component\EntityIdentifierIn;
use Mapbender\CoreBundle\Component\HasInstanceIn;

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
abstract class Source {
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
    
//    /**
//     * Get source type
//     *
//     * @return string 
//     */
//    public abstract function getType();
//    
//    /**
//     * Get manager type 
//     *
//     * @return string 
//     */
//    public abstract function getManagertype();
//    
//    /**
//     * Get bundle name
//     * 
//     * @return string 
//     */
//    public abstract function getClassname();
//    
//    /**
//     * Create Instance
//     */
//    public abstract function createInstance();
    
    public function __toString(){
        return (string) $this->id;
    }
    
    
}
