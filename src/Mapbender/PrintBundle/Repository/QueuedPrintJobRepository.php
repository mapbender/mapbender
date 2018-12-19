<?php


namespace Mapbender\PrintBundle\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Mapbender\PrintBundle\Entity\QueuedPrintJob;

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

    // remaining methods are only for better introspection via concrete return type annotations

    /**
     * @inheritdoc
     * @return QueuedPrintJob|null
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        /** @var QueuedPrintJob|null $result */
        $result = parent::find($id, $lockMode, $lockVersion);
        return $result;
    }

    /**
     * @inheritdoc
     * @return QueuedPrintJob[]
     */
    public function findAll()
    {
        return parent::findAll();
    }

    /**
     * @inheritdoc
     * @return QueuedPrintJob[]
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @inheritdoc
     * @return QueuedPrintJob|null
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        /** @var QueuedPrintJob|null $result */
        $result = parent::findOneBy($criteria, $orderBy);
        return $result;
    }
}
