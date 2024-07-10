<?php

namespace FOM\UserBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Entity\UserLogEntry;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

/**
 * Event listener for failed logins which upscales forced wait time.
 */
#[AsEventListener]
class FailedLoginListener implements EventSubscriberInterface
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected int                    $maxAttempts, // before login is artificially slowed down
        protected int                    $delayTime, // in seconds
        protected string                 $checkInterval, // DateTimeInterval spec for $maxAttempts time window
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [LoginFailureEvent::class => 'onLoginFailure'];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $em = $this->entityManager;
        $passport = $event->getPassport();
        if (!$passport) return;

        $userName = $passport->getUser()?->getUserIdentifier();
        $ipAddress = $_SERVER["REMOTE_ADDR"];
        $repository = $em->getRepository(UserLogEntry::class);
        $userInfo = [
            'userName' => $userName,
            'ipAddress' => $ipAddress,
            'action' => 'login',
            'status' => 'fail',
        ];
        // Log failed login attempt
        $entry = new UserLogEntry(array_merge($userInfo, [
            'context' => [
                'userAgent' => $_SERVER["HTTP_USER_AGENT"],
            ],
        ]));
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
            ->getSingleScalarResult()
        ;

        if ($failedLoginCount >= $this->maxAttempts) {
            sleep($this->delayTime);
        }

        // Garbage collection for log entries
        $repository->createQueryBuilder('p')
            ->delete()
            ->where('p.creationDate < :gcDate')
            ->setParameter('gcDate', new \DateTime("-2 days"))
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
