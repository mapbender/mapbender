<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_regionproperties")
 */
class RegionProperties
{

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Application The configuration entity for the application
     * @ORM\ManyToOne(targetEntity="Application", inversedBy="regionProperties")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $application;

    /**
     * @var string $title The element title
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var array $properties The region properties
     * @ORM\Column(type="array", nullable=true)
     * @Assert\NotBlank()
     */
    protected $properties;

    /**
     * RegionProperties constructor.
     */
    public function __construct()
    {
        $this->properties = array();
    }

    /**
     * Set id. DANGER
     *
     * @param integer $id
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
     * Set name
     *
     * @param string $name
     * @return $this
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
     * Set Application
     *
     * @param Application $application
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
     * Set properties
     *
     * @param array $properties
     * @return $this
     */
    public function setProperties(array $properties = array())
    {
        $this->properties = $properties === null || !is_array($properties) ? array() : $properties;

        return $this;
    }

    /**
     * Get properties
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }
}
