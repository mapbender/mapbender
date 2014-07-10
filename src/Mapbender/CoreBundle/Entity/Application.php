<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Component\Element As ComponentElement;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

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

    private $preparedElements;
    private $screenshotPath;

    /**
     * @var integer $source
     */
    protected $source;

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
     * @ORM\Column(length=1024)
     */
    protected $template;

    /**
     * @ORM\OneToMany(targetEntity="RegionProperties", mappedBy="application", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "asc"})
     */
    public $regionProperties;

    /**
     * @ORM\OneToMany(targetEntity="Element", mappedBy="application", cascade={"persist", "remove"})
     * @ORM\OrderBy({"weight" = "asc"})
     */
    protected $elements;

    /**
     * @ORM\OneToMany(targetEntity="Layerset", mappedBy="application", cascade={"persist", "remove"})
     */
    protected $layersets;

    /**
     * @ORM\ManyToOne(targetEntity="FOM\UserBundle\Entity\User", cascade={"persist"})
     */
    protected $owner;

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
     * @Assert\File(maxSize="102400")
     */
    protected $screenshotFile;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    public function __construct()
    {
        $this->elements = new ArrayCollection();
        $this->layersets = new ArrayCollection();
        $this->regionProperties = new ArrayCollection();
    }

    /**
     * Get entity source type
     *
     * @param int $source
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
     * @param array $template
     */
    public function setRegionProperties($regionProperties)
    {
        $this->regionProperties = $regionProperties;
        return $this;
    }

    /**
     * Get region properties
     *
     * @return array
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
     * @param Mapbender\CoreBundle\Entity\Element $elements
     */
    public function addElements(Element $elements)
    {
        $this->elements[] = $elements;
    }

    /**
     * Get elements
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getElements()
    {
        return $this->elements;
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
     * Get layersets
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getLayersets()
    {
        return $this->layersets;
    }

    /**
     * Set screenshot
     *
     * @param string $screenshot
     */
    public function setScreenshot($screenshot)
    {
        $this->screenshot = $screenshot;
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
     * Set owner
     *
     * @param User $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * Get owner
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set extra assets
     *
     * @param array $extra_assets
     */
    public function setExtraAssets(array $extra_assets)
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
     * @param DateTime $updated
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * Get updated
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

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
                usort($elements,
                    function($a, $b) {
                        return $a->getWeight() - $b->getWeight();
                    });
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

    public function __toString()
    {
        return (string) $this->getId();
    }
    
    public function getNamedRegionProperties()
    {
        $result = array();
        foreach ($this->getRegionProperties() as $regionProperties) {
            $result[$regionProperties->getName()] = $regionProperties;
        }
        return $result;
    }
    
    public function getPropertiesFromRegion($regionName)
    {
        foreach ($this->getRegionProperties() as $regionProperties) {
            if($regionProperties->getName() === $regionName)
                return $regionProperties;
        }
        return null;
    }

    public function copy($container, EntityManager $em)
    {
//        $em->detach($this);
        $app = new Application();
        $app->slug = $this->slug;
        $app->title = $this->title;
        $app->description = $this->description;
        $app->setUpdated(new \DateTime('now'));
        $app->setPublished(false);
        $app->preparedElements = $this->preparedElements;
        $app->screenshotPath = $this->screenshotPath;
        $app->source = $this->source;
        $app->template = $this->template;
        $app->owner = $this->owner;
        $app->screenshot = $this->screenshot;
        $app->extra_assets = $this->extra_assets;
        $app->screenshotFile = $this->screenshotFile;

        $layersetMap = array();
        foreach ($this->layersets as $layerset) {
            $layerset_cloned = $layerset->copy($em);
            $em->persist($layerset_cloned);
            $layersetMap[strval($layerset->getId())] = $layerset_cloned;
            $app->addLayerset($layerset_cloned->setApplication($app));
        }
        if (isset($layerset)) unset($layerset);
        $clonedElmts = array();

        $this->copyElements($container, $em, $app, $clonedElmts, $layersetMap);
        return $app;
    }

    private function copyElements($container, EntityManager $em,
        Application $clonedApp, $clonedElmts, $layersetMap)
    {
        foreach ($this->elements as $element) {
            $options = array();
            $origElmId = strval($element->getId());
            if (key_exists($origElmId, $clonedElmts)) {
                continue;
            }
            $targets = array();
            $form = ComponentElement::getElementForm($container, $this, $element);
            foreach ($form['form']['configuration']->all() as $fieldName =>
                    $fieldValue) {
                $norm = $fieldValue->getNormData();
                $data = $fieldValue->getData();
                $view = $fieldValue->getViewData();
                $extra = $fieldValue->getExtraData();
                if ($norm instanceof Element) { // target Element
                    $targets[$fieldName] = $norm->getId();

                    $fv = $form['form']->createView();
                } else if ($norm instanceof Layerset) { // Map
                    if (key_exists(strval($norm->getId()), $layersetMap)) {
                        $options[$fieldName] = $layersetMap[strval($norm->getId())]->getId();
                    } else {
                        $options[$fieldName] = null;
                    }
                }
            }
            if (count($targets) === 0) {
                $clonedElm = $element->copy($em);
                $em->persist($clonedElm);
                $clonedElm->setApplication($clonedApp);
                foreach ($options as $key => $value) {
                    $configuration = $clonedElm->getConfiguration();
                    $configuration[$key] = $value;
                    $clonedElm->setConfiguration($configuration);
                }
                $em->persist($clonedElm);
                $clonedApp->addElements($clonedElm);
                $clonedElmts[$origElmId] = $clonedElm;
            } else {
                $allTargetsCreated = true;
                foreach ($targets as $name => $value) {
                    if ($value !== null && !key_exists(strval($value),
                            $clonedElmts)) {
                        $allTargetsCreated = false;
                    }
                }
                if (!$allTargetsCreated) {
                    continue;
                }
                $clonedElm = $element->copy($em);
                $em->persist($clonedElm);
                $clonedElm->setApplication($clonedApp);
                foreach ($options as $key => $value) {
                    $configuration = $clonedElm->getConfiguration();
                    $configuration[$key] = $value;
                    $clonedElm->setConfiguration($configuration);
                }

                foreach ($targets as $name => $value) {
                    if (key_exists(strval($value), $clonedElmts)) {
                        $configuration = $clonedElm->getConfiguration();
                        $target = $clonedElmts[strval($value)];
                        $configuration[$name] = $target->getId();
                        $clonedElm->setConfiguration($configuration);
                    }
                }
                $em->persist($clonedElm);
                $clonedApp->addElements($clonedElm);
                $clonedElmts[$origElmId] = $clonedElm;
            }
        }
        if (count($clonedElmts) === count($this->elements)) {
            return;
        } else {
            $this->copyElements($container, $em, $clonedApp, $clonedElmts,
                $layersetMap);
        }
    }

}
