<?php


namespace Mapbender\CoreBundle\Entity\Repository;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * @method Application|null find($id, $lockMode = null, $lockVersion = null)
 * @method Application|null findOneBy(array $criteria, array $orderBy = null)
 * @method Application[] findAll()
 * @method Application[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Application[]|ArrayCollection matching(Criteria $criteria)
 */
class ApplicationRepository extends EntityRepository
{
    /**
     * @param Source $source
     * @param array|null $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return Application[]
     */
    public function findWithInstancesOf(Source $source, array $criteria=null, array $orderBy = null, $limit = null, $offset = null)
    {
        /** @var LayersetRepository $layersetRepository */
        $layersetRepository = $this->getEntityManager()->getRepository('\Mapbender\CoreBundle\Entity\Layerset');
        $applications = array();
        if ($criteria) {
            $matchingApplications = $this->findBy($criteria);
        } else {
            $matchingApplications = null;
        }
        foreach ($layersetRepository->findWithInstancesOf($source) as $layerset) {
            $application = $layerset->getApplication();
            if ($matchingApplications !== null && !in_array($application, $matchingApplications, true)) {
                continue;
            }
            $applicationId = $application->getId();
            if (!array_key_exists($applicationId, $applications)) {
                $applications[$applicationId] = $application;
            }
        }
        $applications = array_values($applications);
        $applications = $this->applyOrderBy($applications, $orderBy);
        $applications = $this->sliceOffsetLimit($applications, $offset, $limit);
        return $applications;
    }

    /**
     * @param SourceInstance $instance
     * @param array|null $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return Application[]
     */
    public function findWithSourceInstance(SourceInstance $instance, array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        /** @var LayersetRepository $layersetRepository */
        $layersetRepository = $this->getEntityManager()->getRepository('\Mapbender\CoreBundle\Entity\Layerset');
        $applications = array();
        if ($criteria) {
            $matchingApplications = $this->findBy($criteria);
        } else {
            $matchingApplications = null;
        }
        foreach ($layersetRepository->findWithInstance($instance) as $layerset) {
            $application = $layerset->getApplication();
            if ($matchingApplications !== null && !in_array($application, $matchingApplications, true)) {
                continue;
            }
            $applicationId = $application->getId();
            if (!array_key_exists($applicationId, $applications)) {
                $applications[$applicationId] = $application;
            }
        }
        $applications = array_values($applications);
        $applications = $this->applyOrderBy($applications, $orderBy);
        $applications = $this->sliceOffsetLimit($applications, $offset, $limit);
        return $applications;
    }

    /**
     * @param Application[] $applications
     * @param array|null $orderBy
     * @return Application[]
     */
    protected function applyOrderBy($applications, $orderBy)
    {
        if ($orderBy) {
            $applications = new ArrayCollection($applications);
            $applications = $applications->matching(Criteria::create()->orderBy($orderBy))->getValues();
        }
        return $applications;
    }

    /**
     * @param Application[] $applications
     * @param int|null $limit
     * @param int|null $offset
     * @return Application[] array
     */
    protected function sliceOffsetLimit($applications, $offset, $limit)
    {
        if ($offset) {
            $applications = array_slice($applications, $offset);
        }
        if ($limit) {
            $applications = array_slice($applications, $limit);
        }
        return $applications;
    }
}
