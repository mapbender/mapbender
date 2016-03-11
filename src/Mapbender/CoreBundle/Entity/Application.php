<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Applicaton entity
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 *
 * @ORM\Entity
 * @UniqueEntity("title")
 * @UniqueEntity("slug")
 * @ORM\Table(name="mb_core_application")
 * @ORM\HasLifecycleCallbacks
 */
class Application
{

    const SOURCE_YAML = 1;
    const SOURCE_DB = 2;

    /**
     * @var Exclude form application menu list
     */
    protected $excludeFromList = false;
    private $preparedElements;
    private $screenshotPath;

    /**
     * @var integer $source
     */
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
     * @ORM\OneToMany(targetEntity="RegionProperties", mappedBy="application", cascade={"remove"})
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $regionProperties;

    /**
     * @ORM\OneToMany(targetEntity="Element", mappedBy="application", cascade={"remove"})
     * @ORM\OrderBy({"weight" = "asc"})
     */
    protected $elements;

    /**
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
     */
    protected $screenshotFile;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $custom_css;

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
     * @return type
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set id
     *
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
     * @param text $description
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
     * @return text
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
     * @return Collection
     */
    public function getRegionProperties()
    {
        return $this->regionProperties;
    }

    /**
     * Get region properties
     *
     * @return array
     */
    public function addRegionProperties(RegionProperties $regionProperties)
    {
        $this->regionProperties[] = $regionProperties;
    }

    /**
     * Add elements
     *
     * @param Element $elements
     */
    public function addElements(Element $elements)
    {
        $this->elements[] = $elements;
    }

    /**
     * Get elements
     *
     * @return Collection
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Set elements
     *
     * @param ArrayCollection $elements elements
     * @return Application
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
     * @return Collection
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
     * @param file $screenshotFile
     * @return $this
     */
    public function setScreenshotFile($screenshotFile)
    {
        $this->screenshotFile = $screenshotFile;

        return $this;
    }

    /**
     * Get screenshotFile
     *
     * @return file
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
     * Get region elements
     *
     * @param null $region
     * @return array|null
     */
    public function getElementsByRegion($region = null)
    {
        if ($this->preparedElements === null) {
            $this->preparedElements = array();

            foreach ($this->getElements() as $element) {
                $elementRegion = $element->getRegion();
                if (!array_key_exists($elementRegion, $this->preparedElements)) {
                    $this->preparedElements[$elementRegion] = array();
                }
                $this->preparedElements[$elementRegion][] = $element;
            }

            foreach ($this->preparedElements as $elementRegion => $elements) {
                usort(
                    $elements,
                    function ($a, $b) {
                        return $a->getWeight() - $b->getWeight();
                    }
                );
            }
        }

        if ($this->preparedElements !== null) {
            if (array_key_exists($region, $this->preparedElements)) {
                return $this->preparedElements[$region];
            } else {
                return null;
            }
        } else {
            return $this->preparedElements;
        }
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
     * @return Exclude
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

}
