<?php
namespace Mapbender\Component;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UrlSessionLoginHandler implements AuthenticationSuccessHandlerInterface {
    /**
     * Constructor
     * @param RouterInterface   $router
     * @param EntityManager     $em
     */
   public function __construct(array $options = array()) {}

    /**
     * This is called when an interactive authentication attempt succeeds.
     * This version takes care of injecting the right SID into the URL for
     * cookie-less environments.
     *
     * @param Request        $request
     * @param TokenInterface $token
     * @return Response The response to return
     */
    function onAuthenticationSuccess(Request $request, TokenInterface $token) {
        $session = $request->getSession();

        $url = $session->get('_security.target_path');
        $session->remove('_security.target_path');

        $url = UrlHelper::setParameters($url, array(
            session_name() => $session->getId()));

        return new RedirectResponse($url);
   }
}
