<?php


namespace Mapbender\Utils;

/**
 * Iterator that loops around the initially passed array infinitely.
 */
class InfiniteCyclicArrayIterator implements \Iterator
{
    /** @var array */
    protected $elements;
    /** @var array */
    protected $keys;
    /** @var int */
    protected $index = 0;
    /** @var int */
    protected $nTotal;

    public function __construct(array $elements)
    {
        $this->elements = array_values($elements);
        $this->keys = array_keys($elements);
        $this->nTotal = count($elements);
    }

    public function next()
    {
        ++$this->index;
        if ($this->index >= $this->nTotal) {
            $this->index = 0;
        }
    }

    public function valid()
    {
        // NOTE: This can never retrun true if the original $elements array was empty,
        //       thus ensuring no looping takes place.
        return $this->index < $this->nTotal;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function current()
    {
        return $this->elements[$this->index];
    }

    public function key()
    {
        return $this->keys[$this->index];
    }
}
