<?php


namespace Mapbender\PrintBundle\Component\Region;


use Mapbender\PrintBundle\Component\TemplateRegion;

/**
 * ArrayAccess / Traversable shim around TemplateRegion objects, so users can do e.g.
 * if (!empty($template['fields']['title']))
 * foreach ($template['fields'] as $name => $field)
 * $something = $template['map']['pageSize']['width']
 *
 */
class RegionCollection implements \ArrayAccess, \IteratorAggregate
{
    /** @var TemplateRegion[] */
    protected $regions = array();

    /**
     * @param TemplateRegion[] $regions
     */
    public function __construct($regions = array())
    {
        foreach ($regions as $name => $region) {
            $this->addMember($name, $region, false);
        }
    }

    /**
     * @param string
     * @return TemplateRegion
     */
    public function getMember($name)
    {
        return $this->regions[$name];
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasMember($name)
    {
        return !empty($this->regions[$name]);
    }

    /**
     * @param string $name
     * @param TemplateRegion $region
     * @param bool $allowReplace
     */
    public function addMember($name, TemplateRegion $region, $allowReplace=false)
    {
        if (!$name || preg_match('#^\d+#', $name)) {
            throw new \InvalidArgumentException("All region names should be non-empty strings, got " . print_r($name, true));
        }
        if (!$allowReplace && array_key_exists($name, $this->regions)) {
            throw new \RuntimeException("Name collision on " . print_r($name, true));
        }
        $this->regions[$name] = $region;
    }

    // foreach support
    public function getIterator()
    {
        return new \ArrayIterator($this->regions);
    }

    // array-style access support
    public function offsetExists($offset)
    {
        return isset($this->regions[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->regions[$offset];
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
