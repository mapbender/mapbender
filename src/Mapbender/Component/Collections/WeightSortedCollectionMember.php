<?php


namespace Mapbender\Component\Collections;


interface WeightSortedCollectionMember
{
    public function getWeight();

    public function setWeight($weight);
}
