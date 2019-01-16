<?php


namespace Mapbender\PrintBundle\Component\Plugin;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\PrintBundle\Entity\QueuedPrintJob;
use Mapbender\PrintBundle\Repository\QueuedPrintJobRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Plugin for queuable print
 *
 * Container-registered at mapbender.print.plugin.queue
 */
class PrintQueuePlugin implements PrintClientHttpPluginInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;
    /** @var TokenStorageInterface */
    protected $tokenStorage;
    /** @var string */
    protected $storagePath;
    /** @var UrlGeneratorInterface */
    protected $router;
    /** @var Filesystem */
    protected $filesystem;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TokenStorageInterface $tokenStorage
     * @param UrlGeneratorInterface $router
     * @param Filesystem $filesystem
     * @param string $storagePath
     */
    public function __construct(EntityManagerInterface $entityManager,
                                TokenStorageInterface $tokenStorage,
                                UrlGeneratorInterface $router,
                                Filesystem $filesystem,
                                $storagePath)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        if (!is_dir($storagePath)) {
            @mkdir($storagePath);
        }
        if (!is_dir($storagePath) || !is_writable($storagePath)) {
            throw new \RuntimeException("Storage path " . var_export($storagePath, true) . " is not a writable directory");
        }
        $this->filesystem = $filesystem;
        $this->storagePath = realpath($storagePath);
    }

    /**
     * @return QueuedPrintJobRepository
     */
    public function getRepository()
    {
        $classReference = 'MapbenderPrintBundle:QueuedPrintJob';
        /** @var QueuedPrintJobRepository $repository */
        $repository = $this->entityManager->getRepository($classReference);
        return $repository;
    }

    public function getDomainKey()
    {
        return 'print-queue';
    }

    public function handleHttpRequest(Request $request, Element $elementEntity)
    {
        $config = $elementEntity->getConfiguration();
        if (empty($config['renderMode']) || $config['renderMode'] != 'queued') {
            return null;
        }
        switch ($request->attributes->get('action')) {
            default:
                return null;
            case 'queuelist':
                $entities = $this->loadQueueList($request, $config);
                return new JsonResponse($this->formatQueueList($entities, $elementEntity));
            case 'open':
                $jobId = $request->query->get('id');
                break;
            case 'delete':
                $jobId = $request->request->get('id');
                break;
        }
        if (!$jobId) {
            throw new BadRequestHttpException("Missing id");
        }
        $entity = $this->getRepository()->find($jobId);
        if (!$entity) {
            throw new NotFoundHttpException();
        }
        $fileName = $entity->getFilename();
        $fullPath = "{$this->storagePath}/{$fileName}";

        switch ($request->attributes->get('action')) {
            default:
                return null;
            case 'open':
                if (!$this->accessAllowed($entity, $config)) {
                    throw new AccessDeniedHttpException();
                }
                if (!file_exists($fullPath) || !is_readable($fullPath)) {
                    throw new NotFoundHttpException();
                }
                $mimeType = 'application/pdf';
                $headers = array(
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => "inline; filename=\"{$fileName}\"",
                );
                return new BinaryFileResponse($fullPath, 200, $headers);
            case 'delete':
                if (!$this->deleteAllowed($entity, $config)) {
                    throw new AccessDeniedHttpException();
                }
                $this->filesystem->remove($fullPath);
                $this->entityManager->remove($entity);
                $this->entityManager->flush();
                return new Response('', 204);
        }
    }

    /**
     * PrintClient interaction support. Returns the name of the Element action that
     * adds a job to the queue. Queue plugin needs to expose this information because
     * it needs the PrintClient Element to prepare the job data before it can be stored,
     * which means it also cannot handle the http action by itself.
     *
     * @return string
     */
    public function getQueueActionName()
    {
        return 'queue';
    }

    /**
     * @param array $jobData
     * @param string $filename
     */
    public function putJob(array $jobData, $filename)
    {
        $entity = new QueuedPrintJob();
        $entity->setPayload($jobData);
        $userId = $this->getCurrentUserId();
        // @todo / TBD: should decide whether anonymous users are allowed to store jobs
        $entity->setUserId($userId);
        $entity->setQueued(new \DateTime());
        $entity->setFilename($filename);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * @param QueuedPrintJob $entity
     * @param mixed[] $configuration
     * @return bool
     */
    protected function accessAllowed($entity, $configuration)
    {
        $queueAccess = ArrayUtil::getDefault($configuration, 'queueAccess', null) ?: 'private';
        return $queueAccess === 'global' || $entity->getUserId() == $this->getCurrentUserId();
    }

    /**
     * @param QueuedPrintJob $entity
     * @param mixed[] $configuration
     * @return bool
     */
    protected function deleteAllowed($entity, $configuration)
    {
        $queueAccess = ArrayUtil::getDefault($configuration, 'queueAccess', null) ?: 'private';
        return $queueAccess === 'global' || $entity->getUserId() == $this->getCurrentUserId();
    }

    /**
     * @param Request $request
     * @param mixed[] $configuration
     * @return QueuedPrintJob[]
     */
    protected function loadQueueList(Request $request, $configuration)
    {
        $queueAccess = ArrayUtil::getDefault($configuration, 'queueAccess', null) ?: 'private';
        if ($queueAccess === 'private') {
            $userId = $this->getCurrentUserId();
            $criteria = array(
                'userId' => $userId,
            );
        } else {
            $criteria = array();
        }
        $order = array(
            'id' => 'DESC',
        );
        return $this->getRepository()->findBy($criteria, $order);
    }

    /**
     * @param QueuedPrintJob[] $entities
     * @param Element $elementEntity
     * @return array[]
     */
    protected function formatQueueList($entities, Element $elementEntity)
    {
        $dataOut = array();
        $elementAction = $this->router->generate('mapbender_core_application_element', array(
            'id' => $elementEntity->getId(),
            'slug' => $elementEntity->getApplication()->getSlug(),
        ));
        $elementConfig = $elementEntity->getConfiguration();
        foreach ($entities as $entity) {
            if ($entity->getCreated()) {
                $calculated = array(
                    'status' => 'mb.print.printclient.joblist.status.finished',
                    'downloadUrl' => rtrim($elementAction, '/') . "/open?id={$entity->getId()}",
                );
                if (($entity->getId() & 1) && $this->deleteAllowed($entity, $elementConfig)) {
                    $calculated += array(
                        'deleteUrl' => rtrim($elementAction, '/') . "/delete",
                    );
                }
            } elseif ($entity->getStarted()) {
                $calculated = array(
                    'status' => 'mb.print.printclient.joblist.status.processing',
                    'downloadUrl' => null,
                );
            } else {
                $calculated = array(
                    'status' => 'mb.print.printclient.joblist.status.pending',
                    'downloadUrl' => null,
                );
            }
            $dataOut[] = $calculated + array(
                'id' => $entity->getId(),
                'ctime' => $this->dateTimeToTimestamp($entity->getQueued()),
            );
        }
        return $dataOut;
    }

    protected function getCurrentUserId()
    {
        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();
        if (!$token || $token instanceof AnonymousToken || !$user) {
            return null;
        }
        if ($user instanceof UserInterface) {
            try {
                // only FOM user and Drupal user have this method,
                // it's not part of the basic UserInterface!
                return $user->getId();
            } catch (\Exception $e) {
                return $user->getUsername();
            }
        } else {
            // user is either an object with __toString or just a string
            return "{$user}";
        }
    }

    /**
     * Convert (nullable) DateTime value from entity to timestamp
     *
     * @param \DateTime|null $date
     * @return int|null
     */
    protected static function dateTimeToTimestamp($date)
    {
        return $date instanceof \DateTime ? $date->getTimestamp() : null;
    }
}
