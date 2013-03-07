<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\HasInstanceIn;
use Mapbender\CoreBundle\Component\InstanceIn;

//use Mapbender\CoreBundle\Entity\Layer;

/**
 * @author Karim Malhas
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_sourceinstance")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * ORM\DiscriminatorMap({"mb_core_sourceinstance" = "SourceInstance"})
 */

abstract class SourceInstance {

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

    public function __construct() {
        
    }

    public function getId(){
        return $this->id;
    }

    public function getTitle(){
        return $this->title;
    }

    public function setTitle($title){
        $this->title = $title;
    }

    /**
     * Get full class name
     *
     * @return string
     */
    public function getClassname(){
        return get_class();
    }

//    

    public function getAssets()
    {
        return array();
    }
    
    /**
     * Set weight
     *
     * @param integer $weight
     */
    public function setWeight($weight) {
        $this->weight = $weight;
        return $this;
    }

    /**
     * Get weight
     *
     * @return integer
     */
    public function getWeight() {
        return $this->weight;
    }
    
    
    
    /**
     *  Set the layerset
     * @param Layerset $layerset Layerset
     * @return Sourceinstance
     */
    public function setLayerset($layerset)
    {
        $this->layerset = $layerset;
        return $this;
    }
    
    /**
     *  Get the layerset
     * @return Layerset
     */
    public function getLayerset()
    {
        $this->layerset;
    }
    
    /**
     * Set enabled
     *
     * @param integer $weight
     */
    public function setEnabled($enabled) {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Get enabled
     *
     * @return integer
     */
    public function getEnabled() {
        return $this->enabled;
    }
    /**
     * Get type
     *
     * @return string
     */
    public abstract function getType();

    /**
     * Get manager type
     *
     * @return string
     */
    public abstract function getManagerType();
    
    /**
     * Get instance source 
     * @return InstanceSource
     */
    public abstract function getSource();
    
    /**
     * Set id
     * @param integer $id id
     */
    public abstract function setId($id);
    
    /**
     * Set configuration of the source instance
     * @param array $configuration configuration of the source instance
     */
    public abstract function setConfiguration($configuration);
    
    /**
     *  Get configuration of the source instance
     */
    public abstract function getConfiguration();


}
