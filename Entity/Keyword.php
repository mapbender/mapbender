<?php
namespace MB\CoreBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Keyword {

    /**
     *  @ORM\Id
     *  @ORM\Column(type="integer")
     *  @ORM\GeneratedValue(strategy="AUTO")
     */

    protected $id;
    /**
     * @ORM\Column(type="string")
     */
    protected $value;

    public function setValue($value){
        $this->value = $value;
    }

    public function getValue(){
        return $this->value;
    }
    

}
