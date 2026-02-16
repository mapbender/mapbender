<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\PreUpdateEventArgs;
use Mapbender\CoreBundle\Validator\Constraints\Scss;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Application entity
 */
#[UniqueEntity('title')]
#[UniqueEntity('slug')]
#[ORM\Entity(repositoryClass: \Mapbender\CoreBundle\Entity\Repository\ApplicationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'mb_core_application')]
class Application
{
    /** YAML based application type */
    const SOURCE_YAML = 1;

    /** Databased application type */
    const SOURCE_DB = 2;

    const MAP_ENGINE_CURRENT = 'current';

    /** @var array YAML roles */
    protected $yamlRoles;

    /** @var integer $source Application source type (self::SOURCE_*) */
    protected $source = self::SOURCE_DB;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected $id;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 128, unique: true)]
    protected $title;

    #[Assert\Regex(pattern: '/^[0-9\-\_a-zA-Z]+$/', message: 'The slug value is wrong.')]
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    protected $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    protected $description;

    #[ORM\Column(length: 1024, nullable: false)]
    protected $template;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 15, nullable: false, options: ['default' => 'current'])]
    protected $map_engine_code = self::MAP_ENGINE_CURRENT;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'persistent_view', type: 'boolean', options: ['default' => false])]
    protected $persistentView = false;

    #[ORM\Column(name: 'splashscreen', type: 'boolean', options: ['default' => true])]
    protected bool $splashscreen = true;

    /**
     * @var RegionProperties[]|ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'application', targetEntity: RegionProperties::class, cascade: ['remove', 'persist'])]
    #[ORM\OrderBy(['id' => 'asc'])]
    protected $regionProperties;

    /**
     * @var Element[]|ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'application', targetEntity: Element::class, cascade: ['remove'])]
    #[ORM\OrderBy(['weight' => 'asc'])]
    protected $elements;

    /**
     * @var Layerset[]|ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'application', targetEntity: Layerset::class, cascade: ['remove'])]
    #[ORM\OrderBy(['title' => 'asc'])]
    protected $layersets;

    #[ORM\Column(type: 'string', length: 256, nullable: true)]
    protected $screenshot;

    #[ORM\Column(type: 'array', nullable: true)]
    protected $extra_assets;

    #[ORM\Column(type: 'datetime')]
    protected $updated;

    /**
     * @Scss
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected $custom_css;

    /**
     * @var array Public options array, this never stored in DB and only for YAML application.
     */
    protected $publicOptions = array();

    public function __construct()
    {
        $this->elements         = new ArrayCollection();
        $this->layersets        = new ArrayCollection();
        $this->regionProperties = new ArrayCollection();
        $this->map_engine_code = self::MAP_ENGINE_CURRENT;
    }

    /**
     * @param int $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param $id
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
     * Set slug
     *
     * @param string $slug
     * @return $this
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
     * Set description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set region properties
     *
     * @param ArrayCollection $regionProperties
     * @return $this
     */
    public function setRegionProperties(ArrayCollection $regionProperties)
    {
        $this->regionProperties = $regionProperties;

        return $this;
    }

    /**
     * Get region properties
     *
     * @return RegionProperties[]|Collection
     */
    public function getRegionProperties()
    {
        return $this->regionProperties;
    }

    /**
     * @param RegionProperties $regionProperties
     */
    public function addRegionProperties(RegionProperties $regionProperties)
    {
        $this->regionProperties[] = $regionProperties;
    }

    /**
     * Add element
     *
     * @param Element $element
     */
    public function addElement(Element $element)
    {
        $this->elements[] = $element;
    }

    /**
     * Get elements
     *
     * @return Element[]|Collection|ArrayCollection
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Set elements
     *
     * @param Collection $elements elements
     * @return $this
     */
    public function setElements(Collection $elements)
    {
        $this->elements = $elements;
        return $this;
    }

    /**
     * Add layersets
     *
     * @param Layerset $layerset
     */
    public function addLayerset(Layerset $layerset)
    {
        $this->layersets[] = $layerset;
    }

    /**
     * Set layersets
     *
     * @param ArrayCollection $layersets layersets
     * @return Application
     */
    public function setLayersets(ArrayCollection $layersets)
    {
        $this->layersets = $layersets;
        return $this;
    }

    /**
     * Get layersets
     *
     * @return Layerset[]|ArrayCollection
     */
    public function getLayersets()
    {
        return $this->layersets;
    }

    /**
     * Read-only informative pseudo-relation
     *
     * @param bool $includeUnowned
     * @return ArrayCollection|SourceInstance[]
     */
    public function getSourceInstances($includeUnowned = false)
    {
        // @todo: figure out if there's an appropriate ORM annotation that can do this without
        //        writing code
        $instances = new ArrayCollection();
        foreach ($this->getLayersets() as $layerset) {
            foreach ($layerset->getInstances($includeUnowned) as $instance) {
                $instances->add($instance);
            }
        }
        return $instances;
    }

    public function getSourceInstanceById(int|string $instanceId): ?SourceInstance
    {
        foreach ($this->getSourceInstances(true) as $instance) {
            /** @var SourceInstance $instance */
            if ($instance->getId() == $instanceId) {
                return $instance;
            }
        }
        return null;
    }

    /**
     * Read-only informative pseudo-relation
     *
     * @param Source $source to filter by specific Source
     * @return ArrayCollection|SourceInstance[]
     */
    public function getInstancesOfSource(Source $source)
    {
        $instances = new ArrayCollection();
        foreach ($this->getLayersets() as $layerset) {
            foreach ($layerset->getInstancesOf($source) as $instance) {
                $instances->add($instance);
            }
        }
        return $instances;
    }

    public function getLayersetsWithInstancesOf(Source $source)
    {
        return $this->getLayersets()->filter(function($layerset) use ($source) {
            /** @var Layerset $layerset */
            return !!$layerset->getInstancesOf($source)->count();
        });
    }

    /**
     * Set screen shot
     *
     * @param string $screenshot
     * @return $this
     */
    public function setScreenshot($screenshot)
    {
        $this->screenshot = $screenshot;

        return $this;
    }

    /**
     * Get screenshot
     *
     * @return string
     */
    public function getScreenshot()
    {
        return $this->screenshot;
    }

    /**
     * Set extra assets
     *
     * @param array|null $extra_assets
     * @return $this
     */
    public function setExtraAssets(?array $extra_assets = null)
    {
        $this->extra_assets = $extra_assets;

        return $this;
    }

    /**
     * Get extra assets
     *
     * @return array
     */
    public function getExtraAssets()
    {
        return $this->extra_assets;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return $this
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set custom_css
     *
     * @param string $custom_css
     * @return $this
     */
    public function setCustomCss($custom_css)
    {
        $this->custom_css = $custom_css;
        return $this;
    }

    /**
     * Get custom_css
     *
     * @return string
     */
    public function getCustomCss()
    {
        return $this->custom_css;
    }

    /**
     * Get elements in a region as a native array instead of a Collection
     * This is a BC construct used exclusively by Resources/views/Application/form-elements.html.twig within ManagerBundle,
     * which uses a |count Twig filter that doesn't support Countables...
     *
     * @param string
     * @return Element[]
     */
    public function getElementsByRegion($region)
    {
        if (!$region) {
            throw new \InvalidArgumentException("Region must not be empty");
        }
        $criteria = new Criteria(Criteria::expr()->eq('region', $region), array(
            'weight' => Criteria::ASC,
        ));
        return $this->getElements()->matching($criteria)->getValues();
    }

    /**
     * Get application ID
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * Get region properties
     *
     * @return RegionProperties[]
     */
    public function getNamedRegionProperties()
    {
        $result = array();
        foreach ($this->getRegionProperties() as $regionProperties) {
            $result[$regionProperties->getName()] = $regionProperties;
        }

        return $result;
    }

    /**
     * Get region properties
     *
     * @param $regionName
     * @return RegionProperties|null
     */
    public function getPropertiesFromRegion($regionName)
    {
        /** @var RegionProperties $regionProperties */
        foreach ($this->getRegionProperties() as $regionProperties) {
            if ($regionProperties->getName() === $regionName) {
                return $regionProperties;
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function getPublicOptions()
    {
        return $this->publicOptions;
    }

    /**
     * @param array $publicOptions
     */
    public function setPublicOptions($publicOptions)
    {
        $this->publicOptions = $publicOptions;
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
     * Is the application based on YAML configuration?
     *
     * @return bool
     */
    public function isYamlBased()
    {
        return $this->source == self::SOURCE_YAML;
    }

    /**
     * Is the application based on Database configuration?
     *
     * @return bool
     */
    public function isDbBased()
    {
        return $this->source == self::SOURCE_DB;
    }

    /**
     * Get the map engine code as a string.
     * @return string
     */
    public function getMapEngineCode()
    {
        return $this->map_engine_code;
    }

    /**
     * @return bool
     */
    public function getPersistentView()
    {
        return $this->persistentView;
    }

    /**
     * @param bool $value
     */
    public function setPersistentView($value)
    {
        $this->persistentView = $value;
    }

    /**
     * @param string $mapEngineCode
     * @return $this
     */
    public function setMapEngineCode($mapEngineCode)
    {
        if ($mapEngineCode !== self::MAP_ENGINE_CURRENT) {
            $mapEngineCode = Application::MAP_ENGINE_CURRENT;
            @trigger_error("Currently, only {$mapEngineCode} is supported", E_USER_DEPRECATED);
        }
        $this->map_engine_code = $mapEngineCode;
        return $this;
    }

    public function isSplashscreen(): bool
    {
        return $this->splashscreen;
    }

    public function setSplashscreen(bool $splashscreen): void
    {
        $this->splashscreen = $splashscreen;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    #[ORM\PostPersist]
    #[ORM\PreUpdate]
    public function bumpUpdate(LifecycleEventArgs $args)
    {
        $this->setUpdated(new \DateTime('now'));
    }
}
