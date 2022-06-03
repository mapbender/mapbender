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
     * @param string
     * @return TemplateRegion
     */
    public function getMember($name)
    {
        return $this->regions[$name][0];
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
     */
    public function addMember($name, TemplateRegion $region)
    {
        if (!$name || preg_match('#^\d+#', $name)) {
            throw new \InvalidArgumentException("All region names should be non-empty strings, got " . print_r($name, true));
        }
        $this->regions += array($name => array());
        $this->regions[$name][] = $region;
    }

    // foreach support
    public function getIterator()
    {
        return new \ArrayIterator(\call_user_func_array('\array_merge', \array_values($this->regions)));
    }

    // array-style access support
    public function offsetExists($offset)
    {
        return isset($this->regions[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->getMember($offset);
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
