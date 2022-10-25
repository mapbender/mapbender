<?php

namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Theme class:
 * Metadata describing the top-level themes where layers available on this server can be classified.
 *
 * @author Paul Schmidt
 * @ORM\Entity
 * @ORM\Table(name="mb_wmts_theme")
 */
class Theme
{

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="WmtsSource",inversedBy="themes")
     * @ORM\JoinColumn(name="wmtssource", referencedColumnName="id")
     */
    protected $source;

    /**
     * @ORM\Column(type="string",nullable=false)
     */
    protected $identifier;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $title;

    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $abstract;

    /**
     * @ORM\Column(type="array",nullable=false);
     */
    protected $layerrefs;

    /**
     * @ORM\ManyToOne(targetEntity="Theme",inversedBy="themes")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    protected $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="Theme",mappedBy="parent", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $themes;

    public function __construct()
    {
        $this->themes = new ArrayCollection();
        $this->layerrefs = array();
    }

    /**
     * @return integer id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param WmtsSource $wmtssource
     * @return Theme
     */
    public function setSource(WmtsSource $wmtssource)
    {
        $this->source = $wmtssource;
        return $this;
    }

    /**
     * @return Theme
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string identifier
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string title
     */
    public function getTitle()
    {
        return $this->title;
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
     * @return string abstract
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * @param string $abstract
     * @return $this
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     * @return array
     */
    public function getLayerRefs()
    {
        return $this->layerrefs;
    }

    /**
     * @param array $layerrefs
     * @return $this
     */
    public function setLayerRefs($layerrefs)
    {
        $this->layerrefs = $layerrefs;
        return $this;
    }

    /**
     * @param string $layerref
     * @return $this
     */
    public function addLayerRef($layerref)
    {
        $this->layerrefs[] = $layerref;
        return $this;
    }

    /**
     * @param Theme|null $parent
     * @return $this
     */
    public function setParent(Theme $parent = NULL)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Theme|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param ArrayCollection $themes
     * @return $this
     */
    public function setThemes(ArrayCollection $themes)
    {
        $this->themes = $themes;
        return $this;
    }

    /**
     * @param Theme $theme
     * @return $this
     */
    public function addTheme($theme)
    {
        $this->themes->add($theme);
        return $this;
    }

    /**
     * @return Theme[]|ArrayCollection
     */
    public function getThemes()
    {
        return $this->themes;
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}
