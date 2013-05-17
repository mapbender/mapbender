<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
//use Doctrine\Common\Collections\ArrayCollection;

/**
 * Source entity
 *
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_state")
 */
class State
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
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    protected $name;

    /**
     * @var string $title The source title
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    protected $serverurl;

    /**
     * @var string $title The source title
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    protected $slug;
    
    /**
     * @var string $title The source title
     * @ORM\Column(type="text", nullable=false)
     */
    protected $json;

    public function __construct()
    {
        
    }

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
     * Set name
     *
     * @param string $name
     * @return State
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set serverurl
     *
     * @param string $serverurl
     * @return State
     */
    public function setServerurl($serverurl)
    {
        $this->serverurl = $serverurl;
        return $this;
    }

    /**
     * Get serverurl
     *
     * @return string serverurl 
     */
    public function getServerurl()
    {
        return $this->serverurl;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return State
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Get slug
     *
     * @return string 
     */
    public function getSlug()
    {
        return $this->slug;
    }
    
    

    /**
     * Set json
     *
     * @param string $json
     * @return State
     */
    public function setJson($json)
    {
        $this->json = $json;
        return $this;
    }

    /**
     * Get json
     *
     * @return string 
     */
    public function getJson()
    {
        return $this->json;
    }

}
