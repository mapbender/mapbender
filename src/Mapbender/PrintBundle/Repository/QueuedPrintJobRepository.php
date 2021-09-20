<?php


namespace Mapbender\PrintBundle\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Mapbender\PrintBundle\Entity\QueuedPrintJob;

/**
 * @method QueuedPrintJob[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method QueuedPrintJob[] findAll()
 * @method QueuedPrintJob|null find($id, $lockMode = null, $lockVersion = null)
 * @method QueuedPrintJob|null findOneBy(array $criteria, array $orderBy = null)
 */
class QueuedPrintJobRepository extends EntityRepository
{
    /**
     * @return QueuedPrintJob[]
     */
    public function findReadyForProcessing()
    {
        return $this->findBy(array(
            'started' => null,
            'created' => null,
        ));
    }

    /**
     * @param \DateTime $cutoff
     * @return QueuedPrintJob[]
     */
    public function findOlderThan(\DateTime $cutoff)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->lt('created', $cutoff))
        ;
        return $this->matching($criteria)->getValues();
    }

    /**
     * Find jobs where processing was started but that haven't finished yet.
     * @return QueuedPrintJob[]
     */
    public function findHung()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('created', null))
            ->andWhere(Criteria::expr()->neq('started', null))
        ;
        return $this->matching($criteria)->getValues();
    }
}
