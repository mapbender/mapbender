<?php

namespace Mapbender\PrintBundle\Component;

use Mapbender\CoreBundle\Component\EntitiesServiceBase;
use Mapbender\PrintBundle\DependencyInjection\MapbenderPrintExtension;
use Mapbender\PrintBundle\Entity\PrintQueue;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PrintQueueManager
 *
 * @package   Mapbender\PrintBundle\Component
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class PrintQueueManager extends EntitiesServiceBase
{
    /** Status if queued queue rendering started before (shouldn't happens) */
    const STATUS_WRONG_QUEUED       = 2;

    /** Status of empty queue */
    const STATUS_QUEUE_EMPTY        = 3;

    /** Status if current rendering isn't finished yet. */
    const STATUS_IN_PROCESS         = 4;

    /** Status if queued not exists */
    const STATUS_QUEUE_NOT_EXISTS   = 5;

    /** @var PriorityVoterInterface */
    private $priorityVoter;

    /**
     * @param ContainerInterface     $container
     * @param PriorityVoterInterface $priorityVoter
     */
    public function __construct(ContainerInterface $container, PriorityVoterInterface $priorityVoter)
    {
        parent::__construct('PrintQueue', $container);
        $this->priorityVoter = $priorityVoter;
    }

    /**
     * Render next from queue
     *
     * @return PrintQueue|int|PrintQueueManager::STATUS_*
     */
    public function renderNext()
    {
        $entity = $this->getNextQueue();
        return $entity ? $this->render($entity) : self::STATUS_QUEUE_EMPTY;
    }

    /**
     * Render PDF
     *
     * @param PrintQueue $entity
     * @return PrintQueue|int|PrintQueueManager::STATUS_*
     */
    public function render(PrintQueue $entity)
    {
        $filePath = $this->getPdFilePath($entity);
        $dir      = $this->container->getParameter(MapbenderPrintExtension::KEY_STORAGE_DIR);
        $fs       = new Filesystem();
        $service  = new PrintService($this->container);

        if (!$fs->exists($dir) || !is_dir($dir)) {
            $fs->mkdir($dir, 0700);
        }

        if(!$entity->isNew()){
            return self::STATUS_WRONG_QUEUED;
        }

        // prevent to start in the meantime of previous rendering
        if($this->isInProcess()){
            return self::STATUS_IN_PROCESS;
        };

        $this->persist($entity->setStarted(new \DateTime()));
        $fs->dumpFile($filePath, $service->doPrint($entity->getPayload()));
        $this->persist($entity->setCreated(new \DateTime()));

        return $entity;
    }

    /**
     * Get next queue
     *
     * @return PrintQueue|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNextQueue()
    {
        return $this->isInProcess() ? null : $this->createQueryBuilder()
            ->where('q.created > 0')
            ->andWhere('q.started < 1')
            ->orderBy('q.priority', 'DESC')
            ->addOrderBy('q.queued', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Generate salt value
     *
     * @param int $length of random value
     * @return string
     */
    public static function genSalt($length = 10)
    {
        $hash = md5(rand() . time());
        return substr(str_shuffle($hash.strtoupper($hash)), 0, $length);
    }

    /**
     * Add print queue
     *
     * @param $payload array Template, layer and etc. settings
     *
     * @return PrintQueue
     */
    public function add(array $payload)
    {
        return $this->persist(
            (new PrintQueue())->setIdSalt(self::genSalt())
                ->setUser($this->getCurrentUser())
                ->setQueued(new \DateTime())
                ->setPayload($payload)
                ->setPriority($this->priorityVoter->getPriority($payload))
        );
    }

    /**
     * Remove print queue and file
     *
     * @param PrintQueue $entity
     * @param bool       $flush Flush entity manager (default=true)
     * @internal param bool $pdf remove PDF file? (default=true)
     * @return array
     */
    public function remove($entity, $flush = true)
    {
        (new Filesystem())->remove($this->getPdFilePath($entity));
        parent::remove($entity, $flush);
    }

    /**
     * Get queue max age
     *
     * @return \DateTime
     */
    public function getMaxAge()
    {
        $maxAge = $this->container->hasParameter(MapbenderPrintExtension::KEY_MAX_AGE)
            ? intval($this->container->getParameter(MapbenderPrintExtension::KEY_MAX_AGE))
            : 3;
        return new \DateTime("-{$maxAge} days");
    }

    /**
     * Removes entities and pdf files older then max-age parameter
     */
    public function clean()
    {
        /** @var PrintQueue $entity */
        $r      = array();

        foreach ($this->createQueryBuilder('q')
                ->select('q.id')
                ->where('q.created < :maxAge')->setParameter('maxAge', $this->getMaxAge())
                ->getQuery()
                ->getResult() as $entity) {
            $r[] = $this->remove($entity);
        };
        $this->getEntityManager()->flush();
        return $r;
    }

    /**
     * Get PDF Parameter
     *
     * @param PrintQueue $entity
     * @return string
     */
    public function getPdFilePath(PrintQueue $entity)
    {
        return $this->container->getParameter(MapbenderPrintExtension::KEY_STORAGE_DIR) . '/' . $entity->getIdSalt() . ".pdf";
    }

    /**
     * Is some queue in process?
     *
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function isInProcess()
    {
        return !!$this->createQueryBuilder()
            ->where('q.started > 1')
            ->andWhere('q.created < 1')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get queue by ID
     *
     * @param $id
     * @return PrintQueue
     */
    public function find($id){
        return $this->getRepository()->findOneBy(array('id' =>  intval($id)));
    }
}