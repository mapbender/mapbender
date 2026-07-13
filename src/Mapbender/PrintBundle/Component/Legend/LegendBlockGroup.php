<?php


namespace Mapbender\PrintBundle\Component\Legend;


class LegendBlockGroup extends LegendBlockCollection
{
    /** @var LegendBlock[] */
    protected $members;

    public function __construct($members = null)
    {
        $this->clear();
        foreach ($members ?: [] as $member) {
            $this->addBlock($member);
        }
    }

    /**
     * @param LegendBlock $block
     */
    public function addBlock($block): void
    {
        $this->members[] = $block;
    }

    /**
     * @return \Iterator|LegendBlock[]
     */
    public function iterateBlocks(): \ArrayIterator
    {
        return new \ArrayIterator($this->members);
    }

    public function clear(): void
    {
        $this->members = [];
    }
}
