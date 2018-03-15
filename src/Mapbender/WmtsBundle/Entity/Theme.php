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
 * ORM\DiscriminatorMap({"mb_wmts_theme" = "Theme"})
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
     * @ORM\OneToMany(targetEntity="Theme",mappedBy="parent")
     * @ORM\OrderBy({"id" = "asc"})
     */
    protected $themes;

    public function __construct()
    {
        $this->themes = new ArrayCollection();
        $this->layerrefs = array();
    }

    /**
     * Get id
     * @return integer id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets a wmts source
     * @param \Mapbender\WmtsBundle\Entity\WmtsSource $wmtssource
     * @return \Mapbender\WmtsBundle\Entity\Theme
     */
    public function setSource(WmtsSource $wmtssource)
    {
        $this->source = $wmtssource;
        return $this;
    }

    /**
     * Gets a wmts source
     * @return \Mapbender\WmtsBundle\Entity\Theme
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get identifier
     * @return string identifier
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set identifier
     * @param string $identifier
     * @return \Mapbender\WmtsBundle\Entity\Theme
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Get title
     * @return string title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title
     * @param string $title
     * @return \Mapbender\WmtsBundle\Entity\Theme
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get abstract
     * @return string abstract
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * Set abstract
     * @param string $abstract
     * @return \Mapbender\WmtsBundle\Entity\Theme
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     * Get layerref
     * @return array layerrefs
     */
    public function getLayerRefs()
    {
        return $this->layerrefs;
    }

    /**
     * Set layerrefs
     * @param array $layerrefs
     * @return \Mapbender\WmtsBundle\Entity\Theme
     */
    public function setLayerRefs($layerrefs)
    {
        $this->layerrefs = $layerrefs;
        return $this;
    }

    /**
     * Add layerref
     * @param string $layerref
     * @return \Mapbender\WmtsBundle\Entity\Theme
     */
    public function addLayerRef($layerref)
    {
        $this->layerrefs[] = $layerref;
        return $this;
    }

    /**
     * Set parent.
     * @param \Mapbender\WmtsBundle\Entity\Theme $parent
     * @return \Mapbender\WmtsBundle\Entity\Theme
     */
    public function setParent(Theme $parent = NULL)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Get parent
     * @return \Mapbender\WmtsBundle\Entity\Theme|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set themes
     * @param ArrayCollection $themes
     * @return \Mapbender\WmtsBundle\Entity\Theme
     */
    public function setThemes(ArrayCollection $themes)
    {
        $this->themes = $themes;
        return $this;
    }

    /**
     * Add a theme
     * @param Theme $theme
     * @return \Mapbender\WmtsBundle\Entity\Theme
     */
    public function addTheme($theme)
    {
        $this->themes->add($theme);
        return $this;
    }

    /**
     * Get themes
     * @return ArrayCollection
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
