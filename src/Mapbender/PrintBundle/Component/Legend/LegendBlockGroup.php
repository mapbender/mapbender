<?php


namespace Mapbender\PrintBundle\Component\Legend;


class LegendBlockGroup extends LegendBlockCollection
{
    /** @var LegendBlock[] */
    protected $members;

    protected $legendEntryName;

    public function __construct($members = null, $legendEntryName = null)
    {
        $this->clear();
        foreach ($members ?: array() as $member) {
            $this->addBlock($member);
        }

        $this->legendEntryName = $legendEntryName;
    }

    /**
     * @param LegendBlock $block
     */
    public function addBlock($block)
    {
        $this->members[] = $block;
    }

    /**
     * @return \Iterator|LegendBlock[]
     */
    public function iterateBlocks()
    {
        return new \ArrayIterator($this->members);
    }

    public function clear()
    {
        $this->members = array();
    }

    public function getLegendEntryName() {
        return $this->legendEntryName;
    }
}
