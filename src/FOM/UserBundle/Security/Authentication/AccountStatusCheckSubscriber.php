<?php


namespace FOM\UserBundle\Security\Authentication;


use FOM\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
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
    public static function getSubscribedEvents()
    {
        return array(
            AuthenticationEvents::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        );
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $evt)
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
