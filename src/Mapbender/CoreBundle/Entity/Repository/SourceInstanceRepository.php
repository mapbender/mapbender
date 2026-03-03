<?php

namespace Mapbender\CoreBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @method SourceInstance|null find($id, $lockMode = null, $lockVersion = null)
 * @method SourceInstance|null findOneBy(array $criteria, array $orderBy = null)
 * @method SourceInstance[] findAll()
 * @method SourceInstance[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method SourceInstance[]|ArrayCollection matching(Criteria $criteria)
 */
class SourceInstanceRepository extends EntityRepository
{
    /**
     * Finds ONLY reusable instances (NOTE: findAll finds combined bound + reusable instances)
     *
     * @param array|null $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return SourceInstance[]
     */
    public function findReusableInstances(?array $criteria = null, ?array $orderBy = null, $limit = null, $offset = null)
    {
        $criteria = array_replace($criteria ?: array(), array(
            'layerset' => null,
        ));
        return $this->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @param array|null $criteria
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     * @return SourceInstance[]
     */
    public function findBoundInstances(?array $criteria = null, ?array $orderBy = null, $limit = null, $offset = null)
    {
        $criteriaSafe = $criteria ?: array();
        if (!isset($criteriaSafe['layerset'])) {
            $criteriaSafe['layerset'] = null;
        }

        return $this->findBy($criteriaSafe, $orderBy, $limit, $offset);
    }
}
