<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_srs")
 */
class SRS
{

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $name the name of the spatial reference system
     * @ORM\Column(type="string", nullable=false, length=15, unique=true)
     */
    protected $name;

    /**
     * @var string $title the title of the spatial reference system
     * @ORM\Column(type="string", length=128)
     */
    protected $title;

    /**
     * @var string The definition of the spatial reference system
     * @ORM\Column(type="string", length=512)
     */
    protected $definition;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string the srs title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the name
     * @return string the srs definition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Sets the srs definition
     * @param string $definition
     * @return $this
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;

        return $this;
    }
}
