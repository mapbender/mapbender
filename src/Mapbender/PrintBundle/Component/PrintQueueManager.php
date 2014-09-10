<?php

namespace Mapbender\PrintBundle\Component;

use FOM\UserBundle\Entity\User;
use Mapbender\CoreBundle\Component\EntitiesServiceBase;
use Mapbender\PrintBundle\DependencyInjection\MapbenderPrintExtension;
use Mapbender\PrintBundle\Entity\PrintQueue;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class PrintQueueManager
 *
 * @package   Mapbender\PrintBundle\Component
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class PrintQueueManager extends EntitiesServiceBase
{
    /** Default max age in days */
    const DEFAULT_MAX_AGE = 3;

    /** Status if queued queue rendering started before (shouldn't happens) */
    const STATUS_WRONG_QUEUED       = 2;

    /** Status of empty queue */
    const STATUS_QUEUE_EMPTY        = 3;

    /** Status if current rendering isn't finished yet. */
    const STATUS_IN_PROCESS         = 4;

    /** Status if queued not exists */
    const STATUS_QUEUE_NOT_EXISTS   = 5;

    /** Event status rendering started */
    const STATUS_RENDERING_STARTED  = 'mapbender.print.rendering.started';

    /** Event status rendering complete */
    const STATUS_RENDERING_COMPLETED = 'mapbender.print.rendering.complete';

    /** Event status rendering saved */
    const STATUS_RENDERING_SAVED = 'mapbender.print.rendering.saved';

    /** Event status rendering save error */
    const STATUS_RENDERING_SAVE_ERROR = 'mapbender.print.rendering.save_error';

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
     * @param bool       $force Force start rendering
     * @return PrintQueue|int|PrintQueueManager::STATUS_
     */
    public function render(PrintQueue $entity, $force = false)
    {
        $filePath = $this->getPdfPath($entity);
        $dir      = $this->container->getParameter(MapbenderPrintExtension::KEY_STORAGE_DIR);
        $fs       = $this->container->get('filesystem');

        if (!$fs->exists($dir) || !is_dir($dir)) {
            $fs->mkdir($dir, 0755);
        }

        if(!$force && !$entity->isNew()){
            $this->dispatch(self::STATUS_WRONG_QUEUED, $entity);
            return self::STATUS_WRONG_QUEUED;
        }

        $this->persist($entity->setStarted(new \DateTime()));
        $this->dispatch(self::STATUS_RENDERING_STARTED, $entity);

        $pdf = $this->container->get('mapbender.print.engine')->doPrint($entity->getPayload());
        $this->persist($entity->setCreated(new \DateTime()));
        $this->dispatch(self::STATUS_RENDERING_COMPLETED, $entity);

        try {
            $fs->dumpFile($filePath, $pdf);
            $this->dispatch(self::STATUS_RENDERING_SAVED, $entity);
        } catch (IOException $e) {
            $this->dispatch(self::STATUS_RENDERING_SAVE_ERROR, $entity);
        }

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
        return $this->createQueryBuilder()
            ->where('q.queued IS NOT NULL')
            ->andWhere('q.started IS NULL')
            ->orderBy('q.priority', 'DESC')
            ->addOrderBy('q.queued', 'ASC')
            ->setMaxResults(1)
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
        // try to detect user...
        $user = isset($payload['userId'])? $this->getUserById($payload['userId']):null;

        return $this->persist(
            (new PrintQueue())->setIdSalt(self::genSalt())
                ->setUser($user)
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
        (new Filesystem())->remove($this->getPdfPath($entity));
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
            : self::DEFAULT_MAX_AGE;
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
    public function getPdfPath(PrintQueue $entity)
    {
        return $this->getPdfPathBySalt($entity->getIdSalt());
    }

    /**
     * Get PDF Parameter
     *
     * @param string $salt
     * @internal string PrintQueue $entity
     * @return string
     */
    public function getPdfPathBySalt($salt)
    {
        return $this->container->getParameter(MapbenderPrintExtension::KEY_STORAGE_DIR) . '/' . $salt . ".pdf";
    }

    /**
     * Get PDF URI by entity
     *
     * @param PrintQueue $entity
     * @return string
     */
    public function getPdfUri(PrintQueue $entity)
    {
        return $this->getPdfUriBySalt($entity->getIdSalt());
    }

    /**
     * Get PDF URI by salt
     *
     * @param $salt
     * @internal param PrintQueue $entity
     * @return string
     */
    public function getPdfUriBySalt($salt)
    {
        return preg_replace('/^.*..\/web\//', '', $this->getPdfPathBySalt($salt));
    }

    /**
     * Is some queue in process?
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @internal param int $test
     * @return PrintQueue
     */
    public function getProcessedQueue()
    {
        return $this->createQueryBuilder()
            ->where('q.started IS NOT NULL')
            ->andWhere('q.created IS NULL')
            ->getQuery()
            ->setMaxResults(1)
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

    /**
     * Fix broken queues
     *
     * @return PrintQueue[]
     */
    public function fixBroken() {
        /** @var PrintQueue $entity */
        $em = $this->getEntityManager();
        $r  = array();
        foreach($this->createQueryBuilder()
            ->where('q.started IS NOT NULL')
            ->andWhere('q.created IS NULL')
            ->getQuery()
            ->getResult() as $entity){
            $em->persist($entity->setStarted(null));
            $r[] = $entity;
        }
        $em->flush();
        return $r;
    }

    /**
     * Get opened queues count
     *
     * @return int
     */
    public function countOpenedQueues()
    {
        return $this->createQueryBuilder()->select('count(q.id)')
            ->where('q.started IS NULL')
            ->andWhere('q.created IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param $userId
     *
     * @return PrintQueue[]
     */
    public function getUserQueueInfos($userId = null) {

        $dateFields    = array('queued', 'created', 'started');
        $queryBuilder = $this->createQueryBuilder()
            ->select('q.id, q.queued, q.created, q.started, q.priority, q.idSalt, u.username')
            ->innerJoin('q.user', 'u')
            ->orderBy('q.priority', 'DESC')
            ->addOrderBy('q.queued', 'ASC');

        if ($userId) {
            /** @var User $user */
            $user = $this->getUserById($userId);
            $queryBuilder
                ->where('q.user = :user')
                ->setParameter('user', $user);
        }

        $queueInfoList = $queryBuilder->getQuery()->getResult();
        foreach ($queueInfoList as &$queueInfo) {
            $queueInfo['uri'] = $this->getPdfUriBySalt($queueInfo['idSalt']);
            foreach ($dateFields as $name) {
                $queueInfo[$name] = self::dateTimeToTimestamp($queueInfo[$name]);
            }
            $queueInfo['status']   = $queueInfo['created'] ? 'ready' : ($queueInfo['started'] ? 'rendering' : 'queued');

        }

        return $queueInfoList;
    }


    /**
     * Get timestamp by datetime object
     *
     * @param $date
     * @return int|null
     */
    public static function dateTimeToTimestamp($date)
    {
        return $date instanceof \DateTime ? $date->getTimestamp() : null;
    }
}