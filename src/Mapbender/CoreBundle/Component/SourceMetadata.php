<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\SourceInstance;

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
     * @return $this
     */
    protected function setUseCommon($useCommon)
    {
        $this->useCommon = $useCommon;
        return $this;
    }

    /**
     * Sets useContact
     * @param boolean $useContact
     * @return $this
     */
    protected function setUseContact($useContact)
    {
        $this->useContact = $useContact;
        return $this;
    }

    /**
     * Sets useUseConditions
     * @param boolean $useUseConditions
     * @return $this
     */
    protected function setUseUseConditions($useUseConditions)
    {
        $this->useUseConditions = $useUseConditions;
        return $this;
    }

    /**
     * Sets useItems
     * @param boolean $useItems
     * @return $this
     */
    protected function setUseItems($useItems)
    {
        $this->useItems = $useItems;
        return $this;
    }

    /**
     * Sets useExtended
     * @param boolean $useExtended
     * @return $this
     */
    protected function setUseExtended($useExtended)
    {
        $this->useExtended = $useExtended;
        return $this;
    }

    /**
     * Sets container
     * @param string|null $container null for none
     * @return $this
     */
    public function setContainer($container = null)
    {
        if ($container === null) {
            $container = SourceMetadata::$CONTAINER_NONE;
        }
        switch ($container) {
            case SourceMetadata::$CONTAINER_ACCORDION:
            case SourceMetadata::$CONTAINER_TABS:
            case SourceMetadata::$CONTAINER_NONE:
                $this->container = $container;
                break;
            default:
                throw new \RuntimeException("Invalid container argument " . print_r($container, true));
        }
        $this->data["container"] = $this->container;
        return $this;
    }

    /**
     * Sets a contenttype
     * @param string|null $contenttype null for default ('element')
     * @return $this
     */
    public function setContenttype($contenttype)
    {
        if ($contenttype === null) {
            $contenttype = SourceMetadata::$CONTENTTYPE_ELEMENT;
        }
        switch ($contenttype) {
            case SourceMetadata::$CONTENTTYPE_ELEMENT:
            case SourceMetadata::$CONTENTTYPE_HTML:
                $this->contenttype = $contenttype;
                break;
            default:
                throw new \RuntimeException("Invalid contenttype argument " . print_r($contenttype, true));
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
     * @param string|null $sourceValue
     * @param string|null  $instanceValue
     * @return string
     * @deprecated for bad wording, use strval (null => '') or formatAlternatives directly
     * @internal
     * @see SourceMetaData::formatAlternatives()
     */
    public static function getNotNull($sourceValue, $instanceValue = null)
    {
        return static::formatAlternatives($sourceValue, $instanceValue, false);
    }

    /**
     * Formats a primary and secondary label into a displayable string. The secondary label appears in round
     * brackets after the first.
     * The secondary label is suppressed, along with its brackets, if it's empty; also if it's equal to the primary
     * label and $avoidSame is true.
     *
     * If the primary label is empty, the secondary label takes its place.
     *
     * @param string|null $sourceValue
     * @param string|null $instanceValue
     * @param bool $avoidSame to avoid repeating equal $sourceValue and $instanceValue
     * @return string
     */
    public static function formatAlternatives($sourceValue, $instanceValue, $avoidSame = true)
    {
        // force nulls to empty strings, allow safe comparison without falsely identifying the string "0" as emptyish
        $sourceValue = strval($sourceValue);
        $instanceValue = strval($instanceValue);
        if ($sourceValue === '') {
            return $instanceValue;
        } elseif ($instanceValue === '') {
            return $sourceValue;
        } elseif ($sourceValue !== $instanceValue || !$avoidSame) {
            return "{$sourceValue} ({$instanceValue})";
        } else {
            return $sourceValue;
        }
    }

    abstract public function getTemplate();

    abstract public function getData(SourceInstance $sourceInstance, $itemId = null);
}
