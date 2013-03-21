<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Source entity
 *
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_keyword")
 */
class Keyword
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
     * @ORM\Column(type="string", nullable=false)
     */
    protected $value;

    /**
     * @var string $title The source title
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $sourceid;

    /**
     * @var string $title The source title
     * @ORM\Column(type="string", nullable=false)
     */
    protected $sourceclass;

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
     * Set value
     *
     * @param string $value
     * @return Keyword
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set sourceid
     *
     * @param object $sourceid
     * @return Keyword
     */
    public function setSourceid($sourceid)
    {
        $this->sourceid = $sourceid;
        return $this;
    }

    /**
     * Get sourceid
     *
     * @return Object 
     */
    public function getSourceid()
    {
        return $this->sourceid;
    }

    /**
     * Set sourceclass
     *
     * @param string $sourceclass
     * @return Keyword
     */
    public function setSourceclass($sourceclass)
    {
        $this->sourceclass = $sourceclass;
        return $this;
    }

    /**
     * Get sourceclass
     *
     * @return string 
     */
    public function getSourceclass()
    {
        return $this->sourceclass;
    }

}
