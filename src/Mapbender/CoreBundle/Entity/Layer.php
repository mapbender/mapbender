<?php

/**
 * TODO: License
 */

namespace Mapbender\CoreBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Layer configuration entity
 *
 * @author Christian Wygoda
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_layer")
 */
class Layer {
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $title The layer title
     * @ORM\Column(type="string", length=128)
     */
    protected $title;

    /**
     * @var string $class The element class
     * @ORM\Column(type="string", length=1024)
     */
    protected $class;

    /**
     * @var array $configuration The element configuration
     * @ORM\Column(type="array", nullable=true)
     */
    protected $configuration;

    /**
     * @var Application The configuration entity for the application
     * @ORM\ManyToOne(targetEntity="Layerset", inversedBy="layers")
     */
    protected $layerset;

    /**
     * @var integer $weight The sorting weight for display
     * @ORM\Column(type="integer")
     */
    protected $weight;

    /**
    * @var SourceInstance
    * @ORM\OneToOne(targetEntity="SourceInstance", cascade={"persist"})
    */
    protected $sourceInstance;



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
     * Set class
     *
     * @param string $class
     */
    public function setClass($class) {
        $this->class = $class;
        return $this;
    }

    /**
     * Get class
     *
     * @return string
     */
    public function getClass() {
        return $this->class;
    }

    /**
     * Set configuration
     *
     * @param array $configuration
     */
    public function setConfiguration($configuration) {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfiguration() {
        return $this->configuration;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     */
    public function setWeight($weight) {
        $this->weight = $weight;
        return $this;
    }

    /**
     * Get weight
     *
     * @return integer
     */
    public function getWeight() {
        return $this->weight;
    }

    /**
     * Set application
     *
     * @param Layerset $layerset
     */
    public function setLayerset(Layerset $layerset) {
        $this->layerset = $layerset;
        return $this;
    }

    /**
     * Get layerset
     *
     * @return Layerset
     */
    public function getLayerset() {
        return $this->layerset;
    }

    /**
    * Set SourceInstance
    * @param SourceInstance $sourceInstance
    */
    public function setSourceInstance(SourceInstance $sourceInstance){
        $this->sourceInstance = $sourceInstance;
    }

    /**
    * Get SourceInstance
    */

    public function getSourceInstance(){
        return $this->sourceInstance;
    }
}

