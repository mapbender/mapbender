<?php

namespace Mapbender\Component;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class UrlSessionLoginHandler
 *
 * @package Mapbender\Component
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 * @deprecated Remove it in 3.0.7. Nowhere used.
 */
class UrlSessionLoginHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * Constructor
     *
     * @param array $options
     * @internal param RouterInterface $router
     * @internal param EntityManager $em
     */
    public function __construct(array $options = array()) { }

    /**
     * This is called when an interactive authentication attempt succeeds.
     * This version takes care of injecting the right SID into the URL for
     * cookie-less environments.
     *
     * @param Request        $request
     * @param TokenInterface $token
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $session = $request->getSession();
        $url     = $session->get('_security.target_path');
        $url     = UrlHelper::setParameters($url, array(session_name() => $session->getId()));

        $session->remove('_security.target_path');

        return new RedirectResponse($url);
    }
}
