<?php


namespace Mapbender\ManagerBundle\Component\Menu;


class TopLevelItem extends MenuItem
{
    /** @var int|null */
    protected $weight;

    /**
     * @param $num
     * @return $this
     */
    public function setWeight($num)
    {
        $this->weight = intval($num);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param TopLevelItem[] $items
     * @return TopLevelItem[]
     */
    public static function sortItems($items)
    {
        usort($items, function($a, $b) {
            /** @var TopLevelItem $a */
            /** @var TopLevelItem $b */
            $weightA = $a->getWeight();
            $weightB = $b->getWeight();
            if ($weightA == $weightB) {
                return 0;
            }

            return ($weightA < $weightB) ? -1 : 1;
        });
        return $items;
    }

    /**
     * @param TopLevelItem[] $items
     * @param string[] $routePrefixBlacklist
     * @return TopLevelItem[]
     */
    public static function filterBlacklistedRoutes($items, $routePrefixBlacklist)
    {
        foreach ($items as $index => $item) {
            foreach ($routePrefixBlacklist as $prefix) {
                if (!$item->filterRoute($prefix)) {
                    unset($items[$index]);
                    break;
                }
            }
        }
        return $items;
    }

}
