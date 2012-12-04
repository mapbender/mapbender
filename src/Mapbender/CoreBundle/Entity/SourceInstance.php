<?php
namespace Mapbender\CoreBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author Karim Malhas
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_sourceinstance")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * ORM\DiscriminatorMap({"mb_core_sourceinstance" = "SourceInstance"})
 */

class SourceInstance {

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
    protected $type;

    public function getId(){
        return $id;
    } 

    public function getTitle(){
        return $this->title;
    }
   
    public function setTitle($title){
        $this->title = $title;
    } 

    public function getType(){
        return $this->type;
    }
   
    public function setType($type){
        $this->type = $type;
    } 

}
