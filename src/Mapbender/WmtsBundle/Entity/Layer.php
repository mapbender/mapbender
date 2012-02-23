<?php
namespace Mapbender\WmtsBundle\Entity;

use Mapbender\WmtsBundle\Component\LayerInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\Common\Collections\ArrayCollection;

/**
* @ORM\Entity
* @ORM\Table(name="layer")
* @ORM\InheritanceType("JOINED")
* @ORM\DiscriminatorColumn(name="discr", type="string")
* @ORM\DiscriminatorMap({"wmtsservice" = "WMTSService", "wmtslayer" = "WMTSLayer", "grouplayer" = "GroupLayer"})
*/
abstract class Layer implements LayerInterface{
    /**
     *  @ORM\Id
     *  @ORM\Column(type="integer")
     *  @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string",nullable="true")
     */
    protected $title = "";
    
    /**
     * @ORM\Column(name="name", type="string", nullable="true")
     */
    protected $identifier = "";
    
    /**
     * @ORM\Column(type="text",nullable="true")
     */
    protected $abstract = "";


    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Set id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Get identifier
     *
     * @return string $identifier
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set abstract
     *
     * @param string $abstract
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
    }

    /**
     * Get abstract
     *
     * @return string $abstract
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

  /**
   * This returns $this->id so that is can be used to keep entity relations across Form submissions in a hidden field
   * If you ask yourself : "Should I change this to something more readable? " The answer is likely "No" - Karim
  */
  public function __toString(){
    return (string) $this->getId();
  }
}
