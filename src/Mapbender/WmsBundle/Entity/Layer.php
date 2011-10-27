<?php
namespace Mapbender\WmsBundle\Entity;
use Mapbender\WmsBundle\Component\LayerInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
* @ORM\Entity
* @ORM\InheritanceType("JOINED")
* @ORM\DiscriminatorColumn(name="discr", type="string")
* @ORM\DiscriminatorMap({"wmsservice" = "WMSService", "wmslayer" = "WMSLayer", "grouplayer" = "GroupLayer"})
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
     * @ORM\Column(type="string", nullable="true")
     */
    protected $name = "";
    
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
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
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

}
