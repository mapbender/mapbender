<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\SourceMetadata;

/**
 * @author Karim Malhas
 * @author Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_sourceinstance")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * ORM\DiscriminatorMap({"mb_core_sourceinstance" = "SourceInstance"})
 */
abstract class SourceInstance
{

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $title The source title
     * @ORM\Column(type="string", nullable=true)
     */
    protected $title;

    /**
     * @ORM\ManyToOne(targetEntity="Layerset", inversedBy="instances", cascade={"refresh"})
     * @ORM\JoinColumn(name="layerset", referencedColumnName="id")
     */
    protected $layerset;

    /**
     * @var integer $weight The sorting weight for display
     * @ORM\Column(type="integer")
     */
    protected $weight;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $enabled = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $basesource = false;

    /**
     *
     * @var Source a source
     */
    protected $source;

    /**
     * Returns an id
     *
     * @return integer id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns a title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets a title
     *
     * @param String $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns a source type
     *
     * @return String type
     */
    public function getType()
    {
        return $this->source->getType();
    }

    /**
     * Returns a manager type
     *
     * @return String a manager type
     */
    public function getManagertype()
    {
        return $this->source->getManagertype();
    }

    /**
     * Returns a full class name
     *
     * @return string
     */
    public function getClassname()
    {
        return get_class();
    }

    /**
     * Get the source assets.
     *
     * Returns an array of references to asset files of the given type.
     * Assets are grouped by css and javascript.
     * References can either be filenames/path which are searched for in the
     * Resources/public directory of the element's bundle or assetic references
     * indicating the bundle to search in:
     *
     * array(
     *   'foo.css'),
     *   '@MapbenderCoreBundle/Resources/public/foo.css'));
     *
     * @return array
     */
    public static function listAssets()
    {
        return array();
    }

    /**
     * Get the source assets.
     *
     * This should be a subset of the static function listAssets. Assets can be
     * removed from the overall list depending on the configuration for
     * example. By default, the same list as by listAssets is returned.
     *
     * @return array
     */
    public function getAssets()
    {
        return $this::listAssets();
    }

    /**
     * Sets a weight
     *
     * @param integer $weight
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Returns a weight
     *
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Sets the layerset
     *
     * @param  Layerset       $layerset Layerset
     * @return $this
     */
    public function setLayerset(Layerset $layerset)
    {
        $this->layerset = $layerset;

        return $this;
    }

    /**
     * Returns the layerset
     * @return Layerset
     */
    public function getLayerset()
    {
        return $this->layerset;
    }

    /**
     * Sets an enabled
     *
     * @param  integer        $enabled
     * @return SourceInstance SourceInstance
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Returns an enabled
     *
     * @return integer
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Sets a basesource
     *
     * @param  boolean        $basesource
     * @return SourceInstance SourceInstance
     */
    public function setBasesource($basesource)
    {
        $this->basesource = $basesource;

        return $this;
    }

    /**
     * Returns a basesource
     *
     * @return bool
     */
    public function isBasesource()
    {
        return $this->basesource;
    }

    /**
     * Sets source
     *
     * @param Source $source Source object
     * @return SourceInstance
     */
    abstract public function setSource($source);

    /**
     * Returns source
     *
     * @return Source
     */
    abstract public function getSource();

    /**
     * Sets an id
     * @param integer $id id
     */
    abstract public function setId($id);

    /**
     * Sets a configuration of a source instance
     *
     * @param array $configuration configuration of a source instance
     */
    abstract public function setConfiguration($configuration);

    /**
     *  Returns a configuration of a source instance
     *
     * @return array instance configuration
     */
    abstract public function getConfiguration();

    /**
     *
     * @return SourceMetadata
     */
    abstract public function getMetadata();


    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getId();
    }
}
