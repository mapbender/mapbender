<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * Collects template variables for source instance views
 * @deprecated this entire thing should be implemented purely in twig
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
     * Container type (s. CONTAINER_TABS, CONTAINER_ACCORDION, CONTAINER_NONE)
     * @var string
     */
    protected $container = 'accordion';
    
    /**
     * @var array
     * @deprecated
     */
    protected $data;

    public function __construct()
    {
        $this->resetData();
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
     * Resets the metadata data.
     */
    protected function resetData()
    {
        $this->data = array(
            "container" => $this->container,
            "sections" => array(),
        );
    }

    /**
     * Add section by name
     *
     * @param string $sectionName
     * @param array  $items
     * @deprecated use formatSection and do the appending yourself
     */
    protected function addMetadataSection($sectionName, array $items)
    {
        $this->data['sections'][] = $this->formatSection($sectionName, $items);
    }

    /**
     * Reformat section data for (legacy) template expectations, where each 'item' entry is
     * itself an array, with a single value mapped to a single key.
     * The key is prefixed with a constant translation key prefix AND the section title (!), then piped
     * through trans for a label. The value is displayed directly.
     *
     * @todo: [template BC break] drop label translation key prefixing, it makes translation usage searches impossible
     * @todo: [template BC break] drop item array nesting. This serves no purpose. PHP preserves array order, always.
     *
     * @param $title
     * @param $items
     * @return array
     */
    protected function formatSection($title, $items)
    {
        $data = array(
            'title' => $title,
            'items' => array(),
        );
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                if (!is_numeric($key)) {
                    throw new \InvalidArgumentException("Cannot have a string key on an array-style item");
                }
                $data['items'][] = $item;
            } else {
                if (is_numeric($key)) {
                    throw new \InvalidArgumentException("Cannot have a numeric key on a scalar-style item");
                }
                $data['items'][] = array($key => $item);
            }
        }
        return $data;
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
