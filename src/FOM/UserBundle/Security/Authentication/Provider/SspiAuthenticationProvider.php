<?php

namespace FOM\UserBundle\Security\Authentication\Provider;

use FOM\UserBundle\Security\Authentication\Token\SspiUserToken;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SspiAuthenticationProvider implements AuthenticationManagerInterface
{
    public function __construct(protected UserProviderInterface $provider, protected UserCheckerInterface $checker)
    {
    }

    public function authenticate(TokenInterface $token)
    {
        $user = $this->provider->loadUserByIdentifier($token->getUsername());

        if ($user) {
            $this->checker->checkPreAuth($user);
            $authToken = new SspiUserToken(true, $user->getRoles());
            $authToken->setUser($user);
            return $authToken;
        }

        throw new AuthenticationException('No such user.');
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof SspiUserToken;
    }

}
