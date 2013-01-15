<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\HasInstanceIn;
use Mapbender\CoreBundle\Component\InstanceIn;

use Mapbender\CoreBundle\Entity\Layer;

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
    * @var SourceInstance
    * @ORM\OneToMany(targetEntity="Layer", mappedBy="sourceInstance", cascade={"persist","refresh", "remove"})
    */
    protected $mblayer;

    public function __construct() {
        $this->mblayer = new ArrayCollection();
    }

//    /**
//     * @var string $alias The source alias
//     * @ORM\Column(type="string", length=128, nullable=true)
//     */
//    protected $type;

    public function getId(){
        return $id;
    }

    public function getTitle(){
        return $this->title;
    }

    public function setTitle($title){
        $this->title = $title;
    }

    public function getMblayer(){
        return $this->mblayer;
    }

    public function setMblayer(ArrayCollection $mblayers){
        $this->mblayer = $mblayers;
        return $this;
    }
    public function addMblayer(Layer $mblayer){
        $this->mblayer->add($mblayer);
        return $this;
    }

    public abstract function getType();
//    public function getType(){
//        return $this->type;
//    }
//
//    public function setType($type){
//        $this->type = $type;
//    }

    /**
     * Get manager type
     *
     * @return string
     */
    public abstract function getManagerType();

    /**
     * Get full class name
     *
     * @return string
     */
    public function getClassname(){
        return get_class();
    }

//    public abstract function getConfiguration();

    public function getAssets()
    {
        return array();
    }
    
    public abstract function getSource();


}
