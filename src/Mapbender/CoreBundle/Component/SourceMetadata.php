<?php
namespace Mapbender\CoreBundle\Component;

/**
 * Class SourceMetadata prepares and renders an OGC Service metadata
 *
 * @author Paul Schmidt
 */
abstract class SourceMetadata
{
    /**
     * Section 'common'
     * @var string
     */
    public static $SECTION_COMMON = 'common';
    /**
     * Section 'useconditions'
     * @var string
     */
    public static $SECTION_USECONDITIONS = 'useconditions';
    /**
     * Section 'contact'
     * @var string
     */
    public static $SECTION_CONTACT = 'contact';
    /**
     * Section 'items'
     * @var string
     */
    public static $SECTION_ITEMS = 'items';
    /**
     * Section 'subitems'
     * @var string
     */
    public static $SECTION_SUBITEMS = 'subitems';
    /**
     * Section 'item'
     * @var string
     */
    public static $SECTION_EXTENDED = 'extended';

    /**
     * Container 'tabs'
     * @var string
     */
    public static $CONTAINER_TABS = 'tabs';

    /**
     * Container 'accordion'
     * @var string
     */
    public static $CONTAINER_ACCORDION = 'accordion';
    
    /**
     * Container 'none'
     * @var string
     */
    public static $CONTAINER_NONE = 'none';

    /**
     * Metadata contenttype - as html site
     * @var integer
     */
    public static $CONTENTTYPE_HTML = 'html';
    
    /**
     * Metadata contenttype - as html element
     * @var integer
     */
    public static $CONTENTTYPE_ELEMENT = 'element';

    /**
     * Use common metadata
     * @var boolean
     */
    protected $useCommon = true;

    /**
     * Use contact metadata
     * @var boolean
     */
    protected $useContact = true;

    /**
     * Use terms of use metadata
     * @var boolean
     */
    protected $useUseConditions = true;

    /**
     * Use items metadata
     * @var boolean
     */
    protected $useItems = true;

    /**
     * Use extended metadata if exists.
     * @var boolean
     */
    protected $useExtended = true;

    /**
     * Container type (s. CONTAINER_TABS, CONTAINER_ACCORDION, CONTAINER_NONE)
     * @var string
     */
    protected $container;
    
    /**
     * Contenttype (s. CONTENTTYPE_HTML, CONTENTTYPE_ELEMENT)
     * @var string
     */
    protected $contenttype;

    /**
     * Metadata
     * @var array
     */
    protected $data;

    /**
     * SourceMetadata constructor.
     *
     * @param string|null $container String from SourceMetadata::$CONTAINER_*
     * @param string|null $contentType
     */
    public function __construct($container = null, $contentType = null)
    {
        $this->setContainer($container);
        $this->setContenttype($contentType);
        $this->resetData();
    }

    /**
     * Returns useCommon.
     * @return boolean
     */
    protected function getUseCommon()
    {
        return $this->useCommon;
    }

    /**
     * Returns useContact.
     * @return boolean
     */
    protected function getUseContact()
    {
        return $this->useContact;
    }

    /**
     * Returns useUseConditions.
     * @return boolean
     */
    protected function getUseUseConditions()
    {
        return $this->useUseConditions;
    }

    /**
     * Returns useItems.
     * @return boolean
     */
    protected function getUseItems()
    {
        return $this->useItems;
    }

    /**
     * Returns useExtended.
     * @return boolean
     */
    protected function getUseExtended()
    {
        return $this->useExtended;
    }

    /**
     * Returns container type.
     * @return string
     */
    protected function getContainer()
    {
        return $this->container;
    }
    
    /**
     * Returns contenttype
     * @return string
     */
    public function getContenttype()
    {
        return $this->contenttype;
    }

    /**
     * Sets useCommon
     * @param boolean $useCommon
     * @return SourceMetadata
     */
    protected function setUseCommon($useCommon)
    {
        $this->useCommon = $useCommon;
        return $this;
    }

    /**
     * Sets useContact
     * @param boolean $useContact
     * @return SourceMetadata
     */
    protected function setUseContact($useContact)
    {
        $this->useContact = $useContact;
        return $this;
    }

    /**
     * Sets useUseConditions
     * @param boolean $useUseConditions
     * @return SourceMetadata
     */
    protected function setUseUseConditions($useUseConditions)
    {
        $this->useUseConditions = $useUseConditions;
        return $this;
    }

    /**
     * Sets useItems
     * @param boolean $useItems
     * @return SourceMetadata
     */
    protected function setUseItems($useItems)
    {
        $this->useItems = $useItems;
        return $this;
    }

    /**
     * Sets useExtended
     * @param boolean $useExtended
     * @return SourceMetadata
     */
    protected function setUseExtended($useExtended)
    {
        $this->useExtended = $useExtended;
        return $this;
    }

    /**
     * Sets container
     * @param string $container
     * @return SourceMetadata
     */
    public function setContainer($container = null)
    {
        if ($container === null) {
            $this->container = SourceMetadata::$CONTAINER_NONE;
        } elseif ($container === SourceMetadata::$CONTAINER_ACCORDION ||
            $container === SourceMetadata::$CONTAINER_TABS || $container === SourceMetadata::$CONTAINER_NONE) {
            $this->container = $container;
        } else {
            $this->container = SourceMetadata::$CONTAINER_NONE;
        }
        $this->data["container"] = $this->container;
        return $this;
    }

    /**
     * Sets a contenttype
     * @param string $contenttype
     * @return \Mapbender\CoreBundle\Component\SourceMetadata
     */
    public function setContenttype($contenttype)
    {
        if ($contenttype === null) {
            $this->contenttype = SourceMetadata::$CONTENTTYPE_ELEMENT;
        } elseif ($contenttype === SourceMetadata::$CONTENTTYPE_ELEMENT ||
            $contenttype === SourceMetadata::$CONTENTTYPE_HTML) {
            $this->contenttype = $contenttype;
        } else {
            $this->contenttype = SourceMetadata::$CONTENTTYPE_ELEMENT;
        }
        $this->data["contenttype"] = $this->contenttype;
        return $this;
    }

        /**
     * Resets the metadata data.
     */
    protected function resetData()
    {
        $this->data = array(
            "container" => $this->container,
            "sections" => array(),
            'contenttype' => $this->contenttype
        );
    }

    /**
     * Returns data.
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Add section by name
     *
     * @param string $sectionName
     * @param array  $items
     */
    protected function addMetadataSection($sectionName, array $items)
    {
        $this->data['sections'][] = array(
            "title" => $sectionName,
            "items" => $items
        );
    }

    /**
     * Get not null
     *
     * @param mixed $sourceValue
     * @param null  $instanceValue
     * @return null|string
     */
    public static function getNotNull($sourceValue, $instanceValue = null)
    {
        if ($instanceValue !== null && $sourceValue !== null && $instanceValue !== $sourceValue) {
            return $sourceValue . " (" . $instanceValue . ")";
        } elseif ($instanceValue !== null && $sourceValue !== null){
            return $sourceValue;
        } elseif ($sourceValue !== null) {
            return $sourceValue;
        } elseif ($instanceValue !== null) {
            return $instanceValue;
        } else {
            return '';
        }
    }

    /**
     * Renders the SourceMetadata.
     * @param boolean $templating
     * @param integer $itemName unic item name
     */
    abstract public function render($templating, $itemName);
}
