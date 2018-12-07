<?php


namespace Mapbender\ManagerBundle\Utils;


use Doctrine\Common\Collections\Collection;
use Mapbender\Component\Collections\WeightSortedCollectionMember;

class WeightSortedCollectionUtil
{
    /**
     * @param Collection $collection
     * @param WeightSortedCollectionMember $member
     */
    public static function removeOne(Collection $collection, $member)
    {
        static::removeOneInternal($collection, $member, true);
    }

    /**
     * @param Collection $collection
     */
    public static function reassignWeights(Collection $collection)
    {
        foreach ($collection->getValues() as $newWeight => $member) {
            /** @var WeightSortedCollectionMember $member */
            $member->setWeight($newWeight);
        }
    }

    /**
     * @param Collection $collection
     * @param WeightSortedCollectionMember $member
     * @param int|null $targetWeight
     */
    public static function updateSingleWeight(Collection $collection, $member, $targetWeight)
    {
        static::removeOneInternal($collection, $member, false);
        $siblings = $collection->getValues();
        $collection->clear();
        foreach ($siblings as $i => $sibling) {
            // NOTE: We deliberately avoid the === comparison here because some entities can have stringified
            //       weights in some contexts, and some controllers do not typecast their request values
            //       properly either.
            if ($i == $targetWeight) {
                // place at desired location inside the list
                $collection->add($member);
            }
            $collection->add($sibling);
        }
        if ($targetWeight >= count($siblings)) {
            // append at the end
            $collection->add($member);
        }
        static::reassignWeights($collection);
    }

    /**
     * @param Collection $collection
     * @param WeightSortedCollectionMember $member
     * @param int|null $targetWeight
     */
    public static function insertWithWeight(Collection $collection, $member, $targetWeight)
    {
        if ($targetWeight === null || $targetWeight >= count($collection)) {
            static::append($collection, $member);
        } elseif ($targetWeight <= 0) {
            static::prepend($collection, $member);
        } else {
            $collection->add($member);
            static::updateSingleWeight($collection, $member, $targetWeight);
        }
    }

    /**
     * @param Collection $collection
     * @param WeightSortedCollectionMember $member
     */
    public static function append(Collection $collection, $member)
    {
        /** @var WeightSortedCollectionMember $member */
        $member->setWeight(count($collection));
        $collection->add($member);
    }

    /**
     * @param Collection $collection
     * @param WeightSortedCollectionMember $member
     */
    public static function prepend(Collection $collection, $member)
    {
        $members = array_merge(array($member), array_values($collection->slice(0)));
        $collection->clear();
        foreach ($members as $newWeight => $newMember) {
            /** @var WeightSortedCollectionMember $newMember */
            $newMember->setWeight($newWeight);
            $collection->add($newMember);
        }
    }

    /**
     * @param Collection $target
     * @param Collection $source
     * @param WeightSortedCollectionMember $member
     * @param int|null $targetWeight
     */
    public static function moveBetweenCollections(Collection $target, Collection $source, $member, $targetWeight = null)
    {
        static::removeOne($source, $member);
        static::insertWithWeight($target, $member, $targetWeight);
    }

    /**
     * @param Collection $collection
     * @param $member
     * @param bool $renumber to trigger weight reassignment
     */
    public static function removeOneInternal(Collection $collection, $member, $renumber)
    {
        $memberKey = $collection->indexOf($member);
        if (false === $memberKey) {
            throw new \RuntimeException("Given object is not a member of the given source collection");
        }
        $collection->remove($memberKey);
        if ($renumber) {
            static::reassignWeights($collection);
        }
    }
}
