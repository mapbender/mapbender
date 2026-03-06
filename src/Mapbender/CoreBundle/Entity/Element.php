<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use FOM\UserBundle\Security\Permission\YamlDefinedPermissionEntity;
use Mapbender\Component\Collections\WeightSortedCollectionMember;
use Mapbender\Component\Enumeration\ScreenTypes;

/**
 * Element configuration entity
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 */
#[ORM\Entity]
#[ORM\Table(name: 'mb_core_element')]
class Element implements WeightSortedCollectionMember, YamlDefinedPermissionEntity
{
    /**
     * @var integer
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 128)]
    protected $title;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 1024)]
    protected $class;

    /**
     * @var array|null
     */
    #[ORM\Column(type: 'array', nullable: true)]
    protected $configuration;

    /**
     * @var Application|null
     */
    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'elements')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected $application;

    /**
     * Name of container region in template
     * @var string|null
     */
    #[ORM\Column]
    protected $region;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected $enabled = true;

    /**
     * Sorting weight within region
     * @var integer|null
     */
    #[ORM\Column(type: 'integer')]
    protected $weight;

    /** @var string[]|null */
    protected $yamlRoles;

    /**
     * Allowable screen type
     * @var string
     */
    #[ORM\Column(type: 'string', length: 7, options: ['default' => 'all'])]
    protected $screenType = 'all';  // = ScreenTypes::ALL

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
    public function getYamlRoles(): ?array
    {
        return $this->yamlRoles;
    }

    /**
     * @param string[]|null $yamlRoles
     */
    public function setYamlRoles(?array $yamlRoles): void
    {
        $this->yamlRoles = $yamlRoles;
    }

    /**
     * @return string
     */
    public function getScreenType()
    {
        return $this->screenType;
    }

    /**
     * @param string $screenType
     * @throws \InvalidArgumentException
     */
    public function setScreenType($screenType)
    {
        if (!in_array($screenType, ScreenTypes::getValidValues(), true)) {
            throw new \InvalidArgumentException("Unsupported screen type value " . print_r($screenType, true));
        }
        $this->screenType = $screenType;
    }

    /**
     * Get a sibling entity in the same application by id.
     *
     * @param integer $id
     * @param bool $sameRegion
     * @return Element|null
     */
    public function getSiblingElement($id, $sameRegion)
    {
        if ($id === null || $id === false) {
            throw new \LogicException("No element sibling can have id " . var_export($id, true));
        }
        if (!$this->getApplication()->isYamlBased()) {
            // Database ids can only be integers, and won't match with a string id.
            $id = intval($id);
        }
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('id', $id))
        ;
        if ($sameRegion) {
            $criteria->andWhere(Criteria::expr()->eq('region', $this->getRegion()));
        }
        return $this->getApplication()->getElements()->matching($criteria)->first() ?: null;
    }

    /**
     * Get a sibling entity in the same application, using an id placed into this entity's
     * configuration array at a given $configPropertyName.
     *
     * @param string $configPropertyName default 'target'
     * @return Element|null
     * @todo: systemically prevent self-targetting and circular references
     */
    public function getTargetElement($configPropertyName = 'target')
    {
        $config = $this->getConfiguration() ?: array();
        if (isset($config[$configPropertyName])) {
            return $this->getSiblingElement($config[$configPropertyName], false);
        } else {
            return null;
        }
    }
}
