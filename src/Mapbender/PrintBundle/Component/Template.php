<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Region\RegionCollection;

/**
 * Full structural description of the first page of the PDF we want to generate.
 * Contains individual 'regions' (such as one named 'map', 'overview' etc) and
 * 'text fields' (such as 'title').
 *
 * Text fields and regions are functionally the same, but kept in separate pools
 * for historical reasons.
 *
 * Simulates an array structure via ArrayAccess for compatibility with legacy access
 * patterns. The simulated array looks sth like:
 * pageSize:
 *    width: <number>
 *    height: <number>
 * orientation: <string>
 * fields: @see RegionCollection
 * <other dynamic string keys>: @see RegionCollection
 */
class Template implements \ArrayAccess
{
    const ORIENTATION_LANDSCAPE = 'landscape';
    const ORIENTATION_PORTRAIT = 'portrait';

    /** @var float in mm*/
    protected $width;
    /** @var float in mm*/
    protected $height;
    /** @var string */
    protected $orientation;
    /** @var RegionCollection */
    protected $textFields;
    /** @var RegionCollection */
    protected $regions;

    /**
     * @param float $width in mm
     * @param float $height in mm
     * @param string $orientation
     */
    public function __construct($width, $height, $orientation)
    {
        if ($width <= 0 || $height <=0) {
            throw new \InvalidArgumentException("Invalid width / height "
                . print_r($width, true) . ' ' . print_r($height, true));
        }
        $this->width = floatval($width);
        $this->height = floatval($height);
        switch ($orientation) {
            case self::ORIENTATION_LANDSCAPE:
            case self::ORIENTATION_PORTRAIT:
                $this->orientation = $orientation;
                break;
            default:
                throw new \InvalidArgumentException("Invalid orientation " . print_r($orientation, true));
        }

        $this->textFields = new RegionCollection();
        $this->regions = new RegionCollection();
    }

    /**
     * @return string
     */
    public function getOrientation()
    {
        return $this->orientation;
    }

    /**
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return RegionCollection|TemplateRegion[]
     */
    public function getRegions()
    {
        return $this->regions;
    }

    /**
     * @return RegionCollection|TemplateRegion[]
     */
    public function getTextFields()
    {
        return $this->textFields;
    }

    /**
     * @param string
     * @return TemplateRegion
     */
    public function getRegion($name)
    {
        return $this->regions->getMember($name);
    }

    /**
     * @param string
     * @return bool
     */
    public function hasRegion($name)
    {
        return $this->regions->hasMember($name);
    }

    /**
     * @param string
     * @return bool
     */
    public function hasTextField($name)
    {
        return $this->textFields->hasMember($name);
    }

    /**
     * @param TemplateRegion $region
     */
    public function addRegion($region)
    {
        $region->setParentTemplate($this);
        $this->regions->addMember($region->getName(), $region);
    }

    /**
     * @param TemplateRegion $field
     */
    public function addTextField($field)
    {
        $field->setParentTemplate($this);
        $this->textFields->addMember($field->getName(), $field);
    }


    public function offsetGet($offset)
    {
        switch ($offset) {
            case 'orientation':
                return $this->getOrientation();
            case 'pageSize':
                return array(
                    'width' => $this->getWidth(),
                    'height' => $this->getHeight(),
                );
            case 'fields':
                return $this->getTextFields();
            default:
                return $this->getRegion($offset);
        }
    }

    public function offsetExists($offset)
    {
        switch ($offset) {
            case 'orientation':
            case 'pageSize':
            case 'fields':
                return true;
            default:
                return $this->hasRegion($offset);
        }
    }

    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException(get_class($this) . " does not support array-style mutation");
    }

    public function offsetUnset($offset)
    {
        throw new \RuntimeException(get_class($this) . " does not support array-style mutation");
    }
}
