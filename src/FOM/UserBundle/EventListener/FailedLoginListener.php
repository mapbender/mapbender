<?php

namespace FOM\UserBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOM\UserBundle\Entity\UserLogEntry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

/**
 * Event listener for failed logins which upscales forced wait time.
 *
 * @author Christian Wygoda
 * @author Andriy Oblivantsev
 */
class FailedLoginListener implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;
    /** @var int */
    protected $maxAttempts;
    /** @var int */
    protected $delayTime;
    /** @var string */
    protected $checkInterval;

    /**
     * @param EntityManagerInterface $entityManager
     * @param int $maxAttempts before login is artificially slowed down
     * @param int $delayTime in seconds
     * @param string $checkInterval DateTimeInterval spec for $maxAttempts time window
     */
    public function __construct(EntityManagerInterface $entityManager,
                                $maxAttempts, $delayTime, $checkInterval)
    {
        $this->entityManager = $entityManager;
        $this->maxAttempts = $maxAttempts;
        $this->delayTime = $delayTime;
        $this->checkInterval = $checkInterval;
    }

    public static function getSubscribedEvents()
    {
        return array(
            AuthenticationEvents::AUTHENTICATION_SUCCESS => 'onLoginSuccess',
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onLoginFailure',
        );
    }

    /**
     * @param AuthenticationEvent $event
     */
    public function onLoginSuccess(AuthenticationEvent $event)
    {
    }

    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onLoginFailure(AuthenticationFailureEvent $event)
    {
        /** @var EntityRepository $repository */

        $em = $this->entityManager;
        $className  = 'FOMUserBundle:UserLogEntry';
        $userName = $event->getAuthenticationToken()->getUsername();
        $ipAddress  = $_SERVER["REMOTE_ADDR"];
        $repository = $em->getRepository($className);
        $userInfo = array(
            'userName' => $userName,
            'ipAddress' => $ipAddress,
            'action' => 'login',
            'status' => 'fail',
        );
        // Log failed login attempt
        $entry = new UserLogEntry(array_merge($userInfo, array(
            'context' => array(
                'userAgent' => $_SERVER["HTTP_USER_AGENT"],
            ),
        )));
        $em->persist($entry);
        $em->flush();

        $failedLoginCount = $repository->createQueryBuilder('p')->select('count(p.id)')
            ->where('p.ipAddress = :ipAddress')
            ->andWhere('p.userName = :userName')
            ->andWhere('p.status = :status')
            ->andWhere('p.action = :action')
            ->andWhere('p.creationDate > :creationDate')
            ->setParameters($userInfo)
            ->setParameter('creationDate', new \DateTime($this->checkInterval))
            ->getQuery()
            ->getSingleScalarResult();

        if ($failedLoginCount >= $this->maxAttempts) {
            sleep($this->delayTime);
        }

        // Garbage collection for log entries
        // TODO: create user log service and refactor here.
        $repository->createQueryBuilder('p')
            ->delete()
            ->where('p.creationDate < :gcDate')
            ->setParameter('gcDate', new \DateTime("-2 days"))
            ->getQuery()
            ->getSingleScalarResult();
    }

}
