<?php

namespace FOM\UserBundle\Security\Firewall;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use FOM\UserBundle\Security\Authentication\Token\SspiUserToken;

class SspiListener
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var AuthenticationManagerInterface */
    protected $manager;

    /**
     * SspiListener constructor.
     * @param TokenStorageInterface $tokenStorage
     * @param AuthenticationManagerInterface $manager
     */
    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $manager) {
        $this->tokenStorage = $tokenStorage;
        $this->manager = $manager;
    }

    public function __invoke(RequestEvent $event)
    {
        $request = $event->getRequest();

        // check if username is set, let it override
        if($request->get('_username')) {
            return;
        }

        // check if another token exists, then skip
        if($this->tokenStorage->getToken() && (!$this->tokenStorage->getToken() instanceof SspiUserToken)) {
            return;
        }

        $server = $request->server;

        $remote_user = $server->get('REMOTE_USER');

        if(!$remote_user) {
            return;
        }

        $cred = explode('\\', $remote_user);
        if (count($cred) == 1) {
            array_unshift($cred, "unknown");
        }

        $token = new SspiUserToken();
        $token->setUser($cred[1]);

        try {
            $token = $this->manager->authenticate($token);
            $this->tokenStorage->setToken($token);
            return;
        } catch(AuthenticationException $failed) {
            $this->tokenStorage->setToken(null);
            return;
        }
    }
}
