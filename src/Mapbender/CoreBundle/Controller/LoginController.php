<?php
namespace Mapbender\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

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
     * @Route("/user/login")
     * @Template()
     * @Method("GET")
     */
    public function loginAction()
    {
        $request = $this->get('request_stack')->getCurrentRequest();
        /*
        if($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $request->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
        }
        */

        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(
                Security::AUTHENTICATION_ERROR
            );
        } elseif (null !== $session && $session->has(Security::AUTHENTICATION_ERROR)) {
            $error = $session->get(Security::AUTHENTICATION_ERROR);
            $session->remove(Security::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(Security::LAST_USERNAME);

        return array(
            'last_username' => $lastUsername,
            'error' => $error,
            'selfregister' => $this->container->getParameter("fom_user.selfregister"),
            'reset_password' => $this->container->getParameter("fom_user.reset_password")
        );
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
