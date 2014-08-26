<?php

namespace Mapbender\PrintBundle\Component;

use Mapbender\CoreBundle\Component\EntitiesServiceBase;
use Mapbender\CoreBundle\Component\Utils\Base62;
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
    /** Status if queued queue rendering started before (shouldn't happens) */
    const STATUS_WRONG_QUEUED       = -1;

    /** Status of empty queue */
    const STATUS_QUEUE_EMPTY        = -3;

    /** Status if current rendering isn't finished yet. */
    const STATUS_IN_PROCESS         = -2;

    /** Status if queued not exists */
    const STATUS_QUEUE_NOT_EXISTS   = -4;

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
        $this->clean();
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
        $this->persist($entity->setCreated(new \DateTime(filemtime($filePath))));

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
            ->where('q.created != null')
            ->andWhere('q.started == null')
            ->orderBy(array('priority' => 'DESC',
                            'queued'   => 'ASC',)
            )
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get queue token
     *
     * @param PrintQueue $entity
     * @return string
     */
    public static function getToken(PrintQueue $entity)
    {
        return Base62::encode($entity->getId().'-'.$entity->getIdSalt());
    }

    /**
     * Get queue by token
     *
     * @param $token string
     */
    public function getByToken($token)
    {
        $this->getRepository()->find(self::decodeToken($token));
    }

    /**
     * Decode token
     *
     * @param $token
     * @return array of ID's
     */
    public static function decodeToken($token)
    {
        list($id, $saltId) = preg_split('/-/', Base62::decode($token));
        return array('id'     => $id,
                     'saltId' => $saltId);
    }

    /**
     * Generate salt value
     *
     * @param int $length of random value
     * @return string
     */
    public static function genSalt($length = 10)
    {
        return substr(str_shuffle(md5(rand() . time())), 0, $length);
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
     * @param bool       $pdf remove PDF file? (default=true)
     * @return array
     */
    public function remove(PrintQueue $entity, $pdf = true)
    {
        return array(
            $this->delete(array('id' => $entity->getId())),
            $pdf ? (new Filesystem())->remove($this->getPdFilePath($entity)) || true : false
        );
    }


    /**
     * Remove entities and pdf file older then max-age parameter
     */
    public function clean()
    {
        /** @var PrintQueue $entity */
        $r      = array();
        $maxAge = intval($this->container->getParameter(MapbenderPrintExtension::KEY_MAX_AGE));
        foreach ($this->createQueryBuilder()
                     ->setParameter("max_age", new \DateTime(strtotime("-{$maxAge} days")))
                     ->where('q.created != null')
                     ->andWhere('q.created < :max_age')
                     ->getQuery()
                     ->getArrayResult() as $entity) {
            $r[] = $this->remove($entity);
        };
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
        return $this->container->getParameter(MapbenderPrintExtension::KEY_STORAGE_DIR) . '/' . $entity->getToken() . ".pdf";
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
            ->where('started != null')
            ->andWhere('created == null')
            ->getQuery()
            ->getOneOrNullResult();
    }
}