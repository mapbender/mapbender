<?php

/**
 * TODO: License
 */

namespace Mapbender\CoreBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Layerset configuration entity
 *
 * @author Christian Wygoda
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_layerset")
 */
class Layerset {
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $title The layerset title
     * @ORM\Column(type="string", length=128)
     */
    protected $title;

    /**
     * @var Application The configuration entity for the application
     * @ORM\ManyToOne(targetEntity="Application", inversedBy="layersets")
     */
    protected $application;

    /**
     * @ORM\OneToMany(targetEntity="Layer", mappedBy="application",
     *     cascade={"persist"})
     */
    protected $layers;

    public function __construct() {
        $this->layers = new ArrayCollection();
    }

    /**
     * Set id. DANGER
     *
     * Set the entity id. DO NOT USE THIS unless you know what you're doing.
     * Probably the only place where this should be used is in the
     * ApplicationYAMLMapper class. Maybe this could be done using a proxy
     * class instead?
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
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
     * Set application
     *
     * @param Application $application
     */
    public function setApplication(Application $application) {
        $this->application = $application;
        return $this;
    }

    /**
     * Get application
     *
     * @return Application
     */
    public function getApplication() {
        return $this->application;
    }

    /**
     * Add layers
     *
     * @param Layer $layer
     */
    public function addLayers(Layer $layers) {
        $this->layers[] = $layers;
    }

    /**
     * Get layers
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getLayers() {
        return $this->layers;
    }
}

