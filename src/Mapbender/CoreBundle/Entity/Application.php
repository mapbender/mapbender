<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Applicaton entity
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_application")
 * @ORM\HasLifecycleCallbacks
 */
class Application {

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
     */
    protected $title;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
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
     * @ORM\OneToMany(targetEntity="Element", mappedBy="application")
     *     cascade={"persist" })
     */
    protected $elements;

    /**
     * @ORM\OneToMany(targetEntity="Element", mappedBy="application")
     *      cascade={"persist"})
     */
    protected $layersets;

    /**
     * @ORM\ManyToOne(targetEntity="FOM\UserBundle\Entity\User")
     *     cascade={"persist"})
     */
    protected $owner;

    /**
     * @ORM\ManyToMany(targetEntity="FOM\UserBundle\Entity\Role")
     *     cascade={"persist"})
     * @ORM\JoinTable(name="mb_application_roles")
     */
    protected $roles;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $published;

    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    protected $screenshot;

    /**
     * @Assert\File(maxSize="102400")
     */
    public $screenshotFile;

    public function __construct() {
        $this->elements = new ArrayCollection();
        $this->layersets = new ArrayCollection();
        $this->roles = new ArrayCollection();
    }

    /**
     * Get entity source type
     *
     * @param int $source
     */
    public function setSource($source) {
        $this->source = $source;
        return $this;
    }

    /**
     * Get type
     *
     * @return type
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug) {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * Set description
     *
     * @param text $description
     */

    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return text
     */
    public function getDescription() {
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
     * Add elements
     *
     * @param Mapbender\CoreBundle\Entity\Element $elements
     */
    public function addElements(\Mapbender\CoreBundle\Entity\Element $elements) {
        $this->elements[] = $elements;
    }

    /**
     * Get elements
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getElements() {
        return $this->elements;
    }

    /**
     * Add layersets
     *
     * @param Layerset $layerset
     */
    public function addLayersets(Layerset $layersets) {
        $this->layersets[] = $layersets;
    }

    /**
     * Get layersets
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getLayersets() {
        return $this->layersets;
    }

    /**
     * Set screenshot
     *
     * @param string $screenshot
     */
    public function setScreenshot($screenshot) {
        $this->screenshot = $screenshot;
    }

    /**
     * Get screenshot
     *
     * @return string
     */
    public function getScreenshot() {
        return $this->screenshot;
    }

    /**
     * Set owner
     *
     * @param User $owner
     */
    public function setOwner($owner) {
        $this->owner = $owner;
        return $this;
    }

    /**
     * Get owner
     *
     * @return User
     */
    public function getOwner() {
        return $this->owner;
    }

    /**
     * Add roles
     *
     * @param Role $role
     */
    public function addRole(Role $role) {
        $this->role[] = $role;
    }

    /**
     * Get allowed roles
     *
     * @return array
     */
    public function getRoles() {
        return $this->roles;
    }

    /**
     * Set published
     *
     * @param boolean $published
     */
    public function setPublished($published) {
        $this->published = $published;
    }

    /**
     * Is published?
     *
     * @return boolean
     */
    public function isPublished() {
        return $this->published;
    }

    public function getElementsByRegion($region = null) {
        if($this->preparedElements === null) {
            $this->preparedElements = array();

            foreach($this->getElements() as $element) {
                $region = $element->getRegion();
                if(!array_key_exists($region, $this->preparedElements)) {
                    $this->preparedElements[$region] = array();
                }
                $this->preparedElements[$region][] = $element;
            }

            foreach($this->preparedElements as $region => $elements) {
                usort($elements, function($a, $b) {
                    return $a->getWeight() - $b->getWeight();
                });
            }
        }

        if($this->preparedElements !== null) {
            if(array_key_exists($region, $this->preparedElements)) {
                return $this->preparedElements[$region];
            } else {
                return null;
            }
        } else {
            return $this->preparedElements;
        }
    }
}

