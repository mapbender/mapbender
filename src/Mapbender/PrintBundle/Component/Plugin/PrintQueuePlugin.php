<?php


namespace Mapbender\PrintBundle\Component\Plugin;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\PrintBundle\Entity\QueuedPrintJob;
use Mapbender\PrintBundle\Repository\QueuedPrintJobRepository;
use Symfony\Component\Filesystem\Exception\IOException;
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
    /**
     * Name for the Element's http action that should submit a job to the queue
     * Making this constant reduces interaction complexity.
     */
    const ELEMENT_ACTION_NAME_QUEUE = 'queue';

    /** @var EntityManagerInterface */
    protected $entityManager;
    /** @var TokenStorageInterface */
    protected $tokenStorage;
    /** @var UrlGeneratorInterface */
    protected $router;
    /** @var Filesystem */
    protected $filesystem;
    /** @var string */
    protected $storagePath;
    /** @var string */
    protected $loadPath;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TokenStorageInterface $tokenStorage
     * @param UrlGeneratorInterface $router
     * @param Filesystem $filesystem
     * @param string $storagePath
     * @param string $loadPath
     */
    public function __construct(EntityManagerInterface $entityManager,
                                TokenStorageInterface $tokenStorage,
                                UrlGeneratorInterface $router,
                                Filesystem $filesystem,
                                $storagePath,
                                $loadPath)
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
        $this->loadPath = rtrim($loadPath, '/\\');
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

        switch ($request->attributes->get('action')) {
            default:
                return null;
            case 'open':
                if (!$this->accessAllowed($entity, $config)) {
                    throw new AccessDeniedHttpException();
                }
                return $this->getOpenResponse($entity);
            case 'delete':
                if (!$this->deleteAllowed($entity, $config)) {
                    throw new AccessDeniedHttpException();
                }
                $fullPath = "{$this->storagePath}/{$fileName}";
                try {
                    $this->filesystem->remove($fullPath);
                } catch (IOException $e) {
                    // No file to delete
                    // This will happen if storagePath != loadPath.
                }
                $this->entityManager->remove($entity);
                $this->entityManager->flush();
                return new Response('', Response::HTTP_NO_CONTENT);
        }
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
            if ($this->deleteAllowed($entity, $elementConfig)) {
                $calculated += array(
                    'deleteUrl' => rtrim($elementAction, '/') . "/delete",
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
     * @param QueuedPrintJob $entity
     * @return Response
     */
    protected function getOpenResponse($entity)
    {
        $fileName = $entity->getFilename();
        $mimeType = 'application/pdf';
        $headers = array(
            'Content-Type' => $mimeType,
            'Content-Disposition' => "inline; filename=\"{$fileName}\"",
        );
        $fullPath = "{$this->loadPath}/{$fileName}";

        if (preg_match('#^[\w]+://#', $fullPath)) {
            // URL. Delegate to fopen wrapper via file_get_contents.
            // NOTE: We do not support proxy configurations here.
            //       Any pair of browser-facing Mapbender + ~dedicated "print queue server"
            //       installs must be able to exchange data directly, without involving
            //       network proxies.
            $content = file_get_contents($fullPath);
            if ($content !== false) {
                return new Response($content, 200, $headers);
            }
        } else {
            if (file_exists($fullPath) && is_readable($fullPath)) {
                return new BinaryFileResponse($fullPath, 200, $headers);
            }
        }
        throw new NotFoundHttpException();
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
