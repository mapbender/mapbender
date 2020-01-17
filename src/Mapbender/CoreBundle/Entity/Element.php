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
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=1024)
     */
    protected $class;

    /**
     * @var array|null
     * @ORM\Column(type="array", nullable=true)
     */
    protected $configuration;

    /**
     * @var Application|null
     * @ORM\ManyToOne(targetEntity="Application", inversedBy="elements")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $application;

    /**
     * Name of container region in template
     * @var string|null
     * @ORM\Column()
     */
    protected $region;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $enabled = true;

    /**
     * Sorting weight within region
     * @var integer|null
     * @ORM\Column(type="integer")
     */
    protected $weight;

    /** @var string[]|null */
    protected $yamlRoles;

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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param array $configuration
     * @return $this
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param string $region
     * @return $this
     */
    public function setRegion($region)
    {
        $this->region = $region;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param boolean $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param integer $weight
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return integer|null
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param Application $application
     * @return $this
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;
        return $this;
    }

    /**
     * @return Application|null
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return string[]|null
     */
    public function getYamlRoles()
    {
        return $this->yamlRoles;
    }

    /**
     * @param string[]|null $yamlRoles
     */
    public function setYamlRoles($yamlRoles)
    {
        $this->yamlRoles = $yamlRoles;
    }
}
