<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Mapbender\CoreBundle\Validator\Constraints\Scss;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Application entity
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 *
 * @UniqueEntity("title")
 * @UniqueEntity("slug")
 * @ORM\Entity
 * @ORM\Table(name="mb_core_application")
 * @ORM\HasLifecycleCallbacks
 */
class Application
{
    /** YAML based application type */
    const SOURCE_YAML = 1;

    /** Databased application type */
    const SOURCE_DB = 2;

    const MAP_ENGINE_OL2 = 'ol2';

    /**  @var bool Exclude form application menu list */
    protected $excludeFromList = false;

    /** @var array YAML roles */
    protected $yamlRoles;

    /** @var integer $source Application source type (self::SOURCE_*) */
    protected $source = self::SOURCE_DB;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=128, unique=true)
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\Regex(
     *     pattern="/^[0-9\-\_a-zA-Z]+$/",
     *     message="The slug value is wrong."
     * )
     * @Assert\NotBlank()
     */
    protected $slug;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(length=1024, nullable=false)
     */
    protected $template;

    /**
     * @var RegionProperties[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="RegionProperties", mappedBy="application", cascade={"remove"})
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $regionProperties;

    /**
     * @var Element[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="Element", mappedBy="application", cascade={"remove"})
     * @ORM\OrderBy({"weight" = "asc"})
     */
    protected $elements;

    /**
     * @var Layerset[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="Layerset", mappedBy="application", cascade={"remove"})
     */
    protected $layersets;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $published;

    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    protected $screenshot;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $extra_assets;

    /**
     * @Assert\File(maxSize="2097152")
     * @var File
     */
    protected $screenshotFile;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Scss
     */
    protected $custom_css;

    /**
     * @var array Public options array, this never stored in DB and only for YAML application.
     */
    protected $publicOptions = array();

    /**
     * Application constructor.
     */
    public function __construct()
    {
        $this->elements         = new ArrayCollection();
        $this->layersets        = new ArrayCollection();
        $this->regionProperties = new ArrayCollection();
    }

    /**
     * Get entity source type
     *
     * @param int $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set id
     *
     * @param $id
     * @return Application
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
     * @internal param array $template
     */
    public function setRegionProperties(ArrayCollection $regionProperties)
    {
        $this->regionProperties = $regionProperties;

        return $this;
    }

    /**
     * Get region properties
     *
     * @return RegionProperties[]|ArrayCollection
     */
    public function getRegionProperties()
    {
        return $this->regionProperties;
    }

    /**
     * Get region properties
     *
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
     * @return Element[]|ArrayCollection
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Set elements
     *
     * @param ArrayCollection $elements elements
     * @return $this
     */
    public function setElements(ArrayCollection $elements)
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
     * Set screenshotFile
     *
     * @param $screenshotFile
     * @return $this
     */
    public function setScreenshotFile($screenshotFile)
    {
        $this->screenshotFile = $screenshotFile;

        return $this;
    }

    /**
     * Get screenshotFile
     * @return File
     */
    public function getScreenshotFile()
    {
        return $this->screenshotFile;
    }

    /**
     * Set extra assets
     *
     * @param array $extra_assets
     * @return $this
     */
    public function setExtraAssets(array $extra_assets = null)
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
     * Set published
     *
     * @param boolean $published
     * @return $this
     */
    public function setPublished($published)
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Is published?
     *
     * @return boolean
     */
    public function isPublished()
    {
        return $this->published;
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
     * Get elements matching $criteria.
     *
     * @param Criteria $criteria
     * @param Collection|null $collection containing Element entities; null to use own elements collection
     * @return Collection filtered by $criteria, by default sorted by region, weight
     */
    public function filterElementCollection(Criteria $criteria, Collection $collection=null)
    {
        if (null === $collection) {
            $collection = $this->getElements();
        }
        $criteria = clone $criteria;
        if (!$criteria->getOrderings()) {
            $criteria->orderBy(array(
                'region' => Criteria::ASC,
                'weight' => Criteria::ASC,
            ));
        }
        return $collection->matching($criteria);
    }

    /**
     * Get elements in a region as a native array instead of a Collection
     * This is a BC construct used exclusively by ManagerBundle:Resources/views/Application/form-elements.html.twig,
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
     * @return array
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
     * @return null
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
     * Hide application from menu list
     *
     * @param $exclude
     * @return $this
     */
    public function setExcludeFromList($exclude)
    {
        $this->excludeFromList = $exclude;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExcludedFromList()
    {
        return $this->excludeFromList;
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
     * Get the map engine code as a string. Currently only 'ol2'...
     *
     * @return string
     */
    public function getMapEngineCode()
    {
        // HACK: return constant
        /**
         * @todo: provide db column + expose in form
         */
        return self::MAP_ENGINE_OL2;
    }

    /**
     * @param LifecycleEventArgs $args
     * @ORM\PostPersist
     * @ORM\PreUpdate
     */
    public function bumpUpdate(LifecycleEventArgs $args)
    {
        $this->setUpdated(new \DateTime('now'));
    }
}
