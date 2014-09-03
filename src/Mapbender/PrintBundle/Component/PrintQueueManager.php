<?php

namespace Mapbender\PrintBundle\Component;

use FOM\UserBundle\Entity\User;
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
     * @return PrintQueue|int|PrintQueueManager::STATUS_*
     */
    public function render(PrintQueue $entity)
    {
        $filePath = $this->getPdFilePath($entity);
        $dir      = $this->container->getParameter(MapbenderPrintExtension::KEY_STORAGE_DIR);
        $fs       = $this->container->get('filesystem');

        if (!$fs->exists($dir) || !is_dir($dir)) {
            $fs->mkdir($dir, 0755);
        }

        if(!$entity->isNew()){
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
    public function getPdFilePath(PrintQueue $entity)
    {
        return $this->container->getParameter(MapbenderPrintExtension::KEY_STORAGE_DIR) . '/' . $entity->getIdSalt() . ".pdf";
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
     * Get User by ID
     *
     * @param $id
     * @internal param array $payload
     * @return User
     */
    private function getUserById($id)
    {
        return $this->container->get('doctrine')
            ->getRepository('FOMUserBundle:User')
            ->findOneBy(array('id' => $id));
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

}