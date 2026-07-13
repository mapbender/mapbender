<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Paul Schmidt
 */
#[ORM\Entity]
#[ORM\Table(name: 'mb_core_regionproperties')]
class RegionProperties
{

    /**
     * @var integer $id
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var Application The configuration entity for the application
     */
    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'regionProperties')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected $application;

    /**
     * @var string $title The element title
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 128)]
    protected $name;

    /**
     * @var array $properties The region properties
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: 'json', nullable: true)]
    protected $properties;

    /**
     * RegionProperties constructor.
     */
    public function __construct()
    {
        $this->properties = [];
    }

    /**
     * Set id. DANGER
     *
     * @param integer $id
     * @return $this
     */
    public function setId($id): static
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
    public function setName($name): static
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
    public function setApplication(Application $application): static
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
    public function setProperties(array $properties = []): static
    {
        $this->properties = $properties === null || !is_array($properties) ? [] : $properties;

        return $this;
    }

    /**
     * Get properties
     *
     * @return array
     */
    public function getProperties()
    {
        $properties = $this->properties;
        // backwards compatibility: generate_button_menu used to be a boolean flag
        if (array_key_exists('generate_button_menu', $properties)) {
            $value = $properties['generate_button_menu'];
            if ($value === true || $value === 1 || $value === '1') {
                $properties['generate_button_menu'] = 'menu_mobile_desktop';
            } elseif ($value === false || $value === 0 || $value === '') {
                $properties['generate_button_menu'] = 'no_menu';
            } elseif (!in_array($value, ['no_menu', 'menu_mobile_desktop', 'menu_mobile', 'menu_desktop'])) {
                $properties['generate_button_menu'] = 'no_menu';
            }
        }
        return $properties;
    }
}
