<?php


namespace Mapbender\Component\Transformer;


class ChangeTrackingEntry
{
    protected $before;
    protected $after;
    protected $occurrences;

    public function __construct($before, $after)
    {
        $this->before = $before;
        $this->after = $after;
        $this->occurrences = 1;
    }

    /**
     * @return int
     */
    public function getOccurrences()
    {
        return $this->occurrences;
    }

    /**
     * @return mixed
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * @return mixed
     */
    public function getAfter()
    {
        return $this->after;
    }

    public function incrementOccurrences()
    {
        ++$this->occurrences;
    }
}
