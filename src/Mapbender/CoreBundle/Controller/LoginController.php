<?php

namespace Mapbender\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    protected AuthenticationUtils $authUtils;
    protected $enableRegistration;
    protected $enablePasswordReset;

    public function __construct(AuthenticationUtils $authUtils,
                                                    $enableRegistration,
                                                    $enablePasswordReset)
    {
        $this->authUtils = $authUtils;
        $this->enableRegistration = $enableRegistration;
        $this->enablePasswordReset = $enablePasswordReset;
    }

    /**
     * @Route("/user/login", methods={"GET"})
     * @return Response
     */
    public function loginAction()
    {
        return $this->render('@MapbenderCore/Login/login.html.twig', array(
            'selfregister' => $this->enableRegistration,
            'last_username' => $this->authUtils->getLastUsername(),
            'error' => $this->authUtils->getLastAuthenticationError(),
            'reset_password' => $this->enablePasswordReset,
        ));
    }

    /**
     * Handled entirely by Symfony form_login / logout extensions.
     * Action is never called. Only here to define urls, so the
     * routing component doesn't throw 404.
     *
     * @Route("/user/logout", name="mapbender_core_login_logout")
     * @Route("/user/login/check", methods={"POST"})
     */
    public function dummyAction()
    {
        throw new \LogicException("Firewall configuration error. The actions /user/logout and /user/login/check should be intercepted.");
    }
}
