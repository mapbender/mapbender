<?php


namespace Mapbender\CoreBundle\Entity\Repository;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * @method Layerset|null find($id, $lockMode = null, $lockVersion = null)
 * @method Layerset|null findOneBy(array $criteria, array $orderBy = null)
 * @method Layerset[] findAll()
 * @method Layerset[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Layerset[]|ArrayCollection matching(Criteria $criteria)
 */
class LayersetRepository extends EntityRepository
{

    /**
     * @param Source $source
     * @param array|null $criteria
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     * @return Layerset[]
     */
    public function findWithInstancesOf(Source $source, array $criteria=null, array $orderBy = null, $limit = null, $offset = null)
    {
        $layersets = array();
        foreach ($this->findBy($criteria ?: array(), $orderBy) as $layerset) {
            /** @todo: extend getInstancesOf method to support immediate inclusion of reusable instances */
            if ($layerset->getInstancesOf($source)->count()) {
                $layersets[] = $layerset;
            } else {
                $inReusables = false;
                foreach ($layerset->getReusableInstanceAssignments() as $assignment) {
                    if ($assignment->getInstance()->getSource() === $source) {
                        $inReusables = true;
                        break;
                    }
                }
                if ($inReusables) {
                    $layersets[] = $layerset;
                }
            }
        }
        /** @var Layerset[] $layersets */
        $layersets = $this->sliceOffsetLimit($layersets, $offset, $limit);
        return $layersets;
    }

    /**
     * @param SourceInstance $instance
     * @param array|null $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return Layerset[]
     */
    public function findWithInstance(SourceInstance $instance, array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        $matches = array();
        foreach ($this->findBy($criteria ?: array(), $orderBy) as $layerset) {
            if ($layerset->getCombinedInstances()->contains($instance)) {
                $matches[] = $layerset;
            }
        }
        /** @var Layerset[] $matches */
        $matches = $this->sliceOffsetLimit($matches, $offset, $limit);
        return $matches;
    }

    /**
     * @param object[] $objects
     * @param int|null $offset
     * @param int|null $limit
     * @return object[]
     */
    protected function sliceOffsetLimit(array $objects, $offset = null, $limit = null)
    {
        if ($offset !== null && intval($offset) !== 0) {
            $objects = array_slice($objects, $offset);
        }
        if ($limit !== null) {
            $objects = array_slice($objects, 0, $limit);
        }
        return $objects;
    }

    /**
     * @param object[] $objects
     * @param array|null $orderBy mapping of attribute => directon
     * @return object[]
     * @see Criteria::orderBy()
     */
    protected function applyOrderBy(array $objects, array $orderBy)
    {
        if ($orderBy) {
            $collection = new ArrayCollection($objects);
            return $collection->matching(Criteria::create()->orderBy($orderBy))->getValues();
        } else {
            return $objects;
        }
    }
}
