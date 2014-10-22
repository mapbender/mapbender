<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\Application as ApplicationComponent;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
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
     * @ORM\Column(length=1024, nullable=false)
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
    public function setRegionProperties(ArrayCollection $regionProperties)
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

    /**
     * Set custom_css
     *
     * @param text $custom_css
     */
    public function setCustomCss($custom_css)
    {
        $this->custom_css = $custom_css;
        return $this;
    }

    /**
     * Get custom_css
     *
     * @return text
     */
    public function getCustomCss()
    {
        return $this->custom_css;
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
                usort($elements, function($a, $b) {
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
            if ($regionProperties->getName() === $regionName)
                return $regionProperties;
        }

        return null;
    }

    public function copy($container, EntityManager $em, $app)
    {
        $app->preparedElements = $this->preparedElements;
        $app->screenshotPath = $this->screenshotPath;
        $app->source = $this->source;
        $app->owner = $this->owner;
        $app->screenshot = $this->screenshot;
        $app->extra_assets = $this->extra_assets;
        $app->screenshotFile = $this->screenshotFile;
        $em->persist($app);
        $layersetMap = array();
        foreach ($this->layersets as $layerset) {
            $instanceMap = array();
            $layerset_cloned = $layerset->copy($em, $instanceMap);
            $layerset_cloned->setApplication($app);
            $em->persist($layerset_cloned);
            $app->addLayerset($layerset_cloned);
            $layersetMap[strval($layerset->getId())] = array('layerset' => $layerset_cloned, 'instanceMap' => $instanceMap);
        }
        if (isset($layerset))
            unset($layerset);

        foreach ($this->getRegionProperties() as $regprops) {
            $clonedRP = $regprops->copy();
            $clonedRP->setApplication($app);
            $app->addRegionProperties($clonedRP);
        }
        $elementsMap = array();
        $em->flush();
        $aclProvider = $container->get('security.acl.provider');
        # save without target
        foreach ($this->elements as $element) {
            $copied = $element->copy($em);
            $copied->setApplication($app);
//            $copied->setConfiguration(array());
            $em->persist($copied);
            $app->addElements($copied);
            $em->persist($app);
            $em->flush();
            $elementsMap[$element->getId()] = $copied;
            try {
                $oid = ObjectIdentity::fromDomainObject($element);
                $acl = $aclProvider->findAcl($oid);
                $newAcl = $aclProvider->createAcl(ObjectIdentity::fromDomainObject($copied));
                foreach ($acl->getObjectAces() as $ace) {
                    $newAcl->insertObjectAce($ace->getSecurityIdentity(), $ace->getMask());
                }
                $aclProvider->updateAcl($newAcl);
            } catch (\Exception $e) {
                $a = 0;
            }
            $em->persist($copied);
            $em->flush();
        }
        $applicationComponent = new ApplicationComponent($container, $this, array());
        foreach ($this->elements as $element) {
            $elmclass = $element->getClass();
            $elemComponent = new $elmclass($applicationComponent, $container, $element);
            $copied = $elemComponent->copyConfiguration($em, $app, $elementsMap, $layersetMap);
            $em->persist($copied);
        }

        return $app;
    }
}
