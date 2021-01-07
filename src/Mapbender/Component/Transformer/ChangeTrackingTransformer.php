<?php


namespace Mapbender\Component\Transformer;


class ChangeTrackingTransformer implements OneWayTransformer
{
    /** @var OneWayTransformer */
    protected $implementation;
    /** @var bool */
    protected $strictEquality;
    /** @var ChangeTrackingEntry[] */
    protected $changes = array();
    /** @var ChangeTrackingEntry[] */
    protected $unchanged = array();

    /**
     * @param OneWayTransformer $implementation
     * @param bool $strictEquality
     */
    public function __construct(OneWayTransformer $implementation, $strictEquality = true)
    {
        $this->implementation = $implementation;
        $this->strictEquality = $strictEquality;
    }

    public function process($x)
    {
        $rv = $this->implementation->process($x);
        $this->checkChange($x, $rv);
        return $rv;
    }

    /**
     * @return ChangeTrackingEntry[]
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * @return ChangeTrackingEntry[]
     */
    public function getUnchanged()
    {
        return $this->unchanged;
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @return bool
     */
    protected function compare($a, $b)
    {
        if ($this->strictEquality) {
            return $a === $b;
        } else {
            return $a == $b;
        }
    }

    protected function checkChange($before, $after)
    {
        if ($this->compare($before, $after)) {
            $this->trackUnchanged($before);
        } else {
            $this->trackChanged($before, $after);
        }
    }

    protected function trackUnchanged($before)
    {
        $entry = $this->locateEntry($this->unchanged, $before);
        if ($entry) {
            $entry->incrementOccurrences();
        } else {
            $entry = new ChangeTrackingEntry($before, $before);
            $this->unchanged[] = $entry;
        }
    }

    protected function trackChanged($before, $after)
    {
        $entry = $this->locateEntry($this->changes, $before);
        if ($entry) {
            $entry->incrementOccurrences();
        } else {
            $entry = new ChangeTrackingEntry($before, $after);
            $this->changes[] = $entry;
        }
    }

    /**
     * @param ChangeTrackingEntry[] $entryList
     * @param mixed $beforeValue
     * @return ChangeTrackingEntry|null
     */
    protected function locateEntry($entryList, $beforeValue)
    {
        foreach ($entryList as $entry) {
            if ($this->compare($beforeValue, $entry->getBefore())) {
                return $entry;
            }
        }
        return null;
    }
}
