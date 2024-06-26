<?php


namespace FOM\UserBundle\Security\Authentication;


use FOM\UserBundle\EventListener\FailedLoginListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\DisabledException;

/**
 * Tests user account status and denies login if user object returns false from an "isEnabled" method
 * or "isAccountNonExpiredMethod".
 * This replaces automatic recognition of (deprecated and removed in Sf5) AdvancedUserInterface implementations.
 *
 * @see https://symfony.com/doc/5.4/security.html#authentication-events
 * (not documented for 4.4, but implemented equally)
 */
class AccountStatusCheckSubscriber implements EventSubscriberInterface
{
    public function __construct(protected ?FailedLoginListener $loggingFailureSubscriber = null)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
        );
    }

    public function onAuthenticationSuccess(AuthenticationEvent $evt)
    {
        $this->check($evt);
    }

    public function check(AuthenticationEvent $evt)
    {
        $user = $evt->getAuthenticationToken()->getUser();
        if (\is_object($user)) {
            // NOTE: Exception messages are 1:1 from (deprecated) upstream logic
            //       These will be localized through existing translation keys
            /** @see \Symfony\Component\Security\Core\User\UserChecker::checkPreAuth */
            if (\method_exists($user, 'isEnabled') && !$user->isEnabled()) {
                $e = new DisabledException('User account is disabled.');
                $e->setUser($user);
                throw $e;
            }

            if (\method_exists($user, 'isAccountNonExpired') && !$user->isAccountNonExpired()) {
                $e = new AccountExpiredException('User account has expired.');
                $e->setUser($user);
                throw $e;
            }
        }
    }
}
