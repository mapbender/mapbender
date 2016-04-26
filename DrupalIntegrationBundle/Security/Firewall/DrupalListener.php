<?php

namespace Mapbender\DrupalIntegrationBundle\Security\Firewall;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Mapbender\DrupalIntegrationBundle\Security\Authentication\Token\DrupalUserToken;
use Mapbender\DrupalIntegrationBundle\Security\User\DrupalUser;


class DrupalListener implements ListenerInterface
{
    protected $securityContext;
    protected $authenticationManager;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager)
    {
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
    }

    public function handle(GetResponseEvent $event)
    {
        global $user;

        if(null !== $user && $user->uid != 0) {
            $drupalUser = new DrupalUser($user);
            $token = new DrupalUserToken($drupalUser);

            try {
                //$authToken = $this->authenticationManager->authenticate($token);
                $this->securityContext->setToken($token);
            } catch (AuthenticationException $failed) {
                // Deny authentication with a '403 Forbidden' HTTP response
                $response = new Response();
                $response->setStatusCode(403);
                $event->setResponse($response);
            }
        }
    }
}
