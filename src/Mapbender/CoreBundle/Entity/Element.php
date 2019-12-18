<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Element configuration entity
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 *
 * @ORM\Entity(repositoryClass="ElementRepository")
 * @ORM\Table(name="mb_core_element")
 */
class Element
{

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $title The element title
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @var string $class The element class
     * @ORM\Column(type="string", length=1024)
     */
    protected $class;

    /**
     * @var array $configuration The element configuration
     * @ORM\Column(type="array", nullable=true)
     */
    protected $configuration;

    /**
     * @var Application The configuration entity for the application
     * @ORM\ManyToOne(targetEntity="Application", inversedBy="elements")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $application;

    /**
     * @var string $region The template region for the element
     * @ORM\Column()
     */
    protected $region;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $enabled = true;

    /**
     * @var integer $weight The sorting weight for display
     * @ORM\Column(type="integer")
     */
    protected $weight;

    /** @var array */
    protected $yamlRoles;

    /**
     * Element constructor.
     */
    public function __construct()
    {
        $this->enabled = true;
    }

    /**
     * @param mixed $id (integer, might be a string in Yaml-defined applications)
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set title
     *
     * @param string $title
     * @return $this
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
     * Set class
     *
     * @param string $class
     * @return $this
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set configuration
     *
     * @param array $configuration
     * @return $this
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Set region
     *
     * @param string $region
     * @return $this
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Is enabled?
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set application
     *
     * @param \Mapbender\CoreBundle\Entity\Application $application
     * @return $this
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * Get application
     *
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return string Element ID
     */
    public function __toString()
    {
        return (string) $this->id;
    }

    /**
     * @return array
     */
    public function getYamlRoles()
    {
        return $this->yamlRoles;
    }

    /**
     * @param array $yamlRoles
     */
    public function setYamlRoles($yamlRoles)
    {
        $this->yamlRoles = $yamlRoles;
    }

    /**
     * @return string|null
     */
    public function getDescription() {
        return '';
    }
}
