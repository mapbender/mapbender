<?php
namespace Mapbender\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * User controller.
 * Copied into Mapbender from FOM v3.0.6.4.
 * See https://github.com/mapbender/fom/blob/v3.0.6.4/src/FOM/UserBundle/Controller/LoginController.php
 *
 * @author Christian Wygoda
 * @author Paul Schmidt
 */
class LoginController extends Controller
{
    /**
     * User login
     *
     * @Route("/user/login", methods={"GET"})
     * @return Response
     */
    public function loginAction()
    {
        /** @var AuthenticationUtils $authenticationUtils */
        $authenticationUtils = $this->get('security.authentication_utils');
        $error = $authenticationUtils->getLastAuthenticationError(true);
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@MapbenderCore/Login/login.html.twig', array(
            'last_username' => $lastUsername,
            'error' => $error,
            'selfregister' => $this->getParameter("fom_user.selfregister"),
            'reset_password' => $this->getParameter("fom_user.reset_password"),
        ));
    }

    /**
     * @Route("/user/login/check")
     */
    public function loginCheckAction()
    {
        //Don't worry, this is actually intercepted by the security layer.
    }

    /**
     * @Route("/user/logout")
     */
    public function logoutAction()
    {
        //Don't worry, this is actually intercepted by the security layer.
    }
}
