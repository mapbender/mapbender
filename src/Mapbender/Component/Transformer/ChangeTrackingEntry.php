<?php


namespace Mapbender\Component\Transformer;


class ChangeTrackingEntry
{
    protected int $occurrences;

    public function __construct(protected $before, protected $after)
    {
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

    public function incrementOccurrences(): void
    {
        ++$this->occurrences;
    }
}
