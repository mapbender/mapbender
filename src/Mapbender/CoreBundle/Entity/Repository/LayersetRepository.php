<?php


namespace Mapbender\CoreBundle\Entity\Repository;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;

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
            /** @todo: extend getInstancesOf method to support immediate inclusing of reusable instances */
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
        if ($offset) {
            $layersets = array_slice($layersets, $offset);
        }
        if ($limit) {
            $layersets = array_slice($layersets, $limit);
        }
        return $layersets;
    }
}
