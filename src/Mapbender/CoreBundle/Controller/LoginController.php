<?php
namespace Mapbender\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @Route("/user/login/check", methods={"POST"}, name="mapbender_core_login_logincheck")
     * @param Request $request
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $form = $this->createForm('Mapbender\CoreBundle\Form\Type\LoginType', null, array(
            'action' => $this->generateUrl('mapbender_core_login_logincheck'),
        ));

        if ($request->getMethod() === 'GET') {
            /** @var AuthenticationUtils $authenticationUtils */
            $authenticationUtils = $this->get('security.authentication_utils');
            $error = $authenticationUtils->getLastAuthenticationError(true);
            $lastUsername = $authenticationUtils->getLastUsername();
            $form->get('_username')->setData($lastUsername);
            if ($error) {
                /** @var TranslatorInterface $translator */
                $translator = $this->get('translator');
                $form->addError(new FormError($translator->trans($error->getMessage())));
            }
        }

        return $this->render('@MapbenderCore/Login/login.html.twig', array(
            'form' => $form->createView(),
            'selfregister' => $this->getParameter("fom_user.selfregister"),
            'reset_password' => $this->getParameter("fom_user.reset_password"),
        ));
    }

    /**
     * @Route("/user/logout")
     */
    public function logoutAction()
    {
        //Don't worry, this is actually intercepted by the security layer.
    }
}
