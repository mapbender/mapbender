<?php
namespace Mapbender\CoreBundle\Controller;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
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
class LoginController
{
    /** @var FormFactoryInterface */
    protected $formFactory;
    /** @var \Twig\Environment */
    protected $templateEngine;
    /** @var AuthenticationUtils */
    protected $authUtils;
    protected $enableRegistration;
    protected $enablePasswordReset;

    public function __construct(FormFactoryInterface $formFactory,
                                \Twig\Environment $templateEngine,
                                AuthenticationUtils $authUtils,
                                $enableRegistration, $enablePasswordReset)
    {
        $this->formFactory = $formFactory;
        $this->templateEngine = $templateEngine;
        $this->authUtils = $authUtils;
        $this->enableRegistration = $enableRegistration;
        $this->enablePasswordReset = $enablePasswordReset;
    }

    /**
     * User login
     *
     * @Route("/user/login", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $lastUsername = $this->authUtils->getLastUsername();
        $form = $this->formFactory->createNamed(null, 'Mapbender\CoreBundle\Form\Type\LoginType', null, array(
            'action' => 'login/check',
            // We will get back here from a redirect without receiving
            // any form data at all. CSRF token validation will always
            // fail, so disable it.
            'csrf_protection' => false,
        ));
        $error = $this->authUtils->getLastAuthenticationError(true);
        if ($error) {
            // Trigger field validation
            $form->submit(array(
                '_username' => $lastUsername ?: '',
                '_password' => '',
            ));
            $form->addError(new FormError($error->getMessage()));
        }

        return new Response($this->templateEngine->render('@MapbenderCore/Login/login.html.twig', array(
            'form' => $form->createView(),
            'selfregister' => $this->enableRegistration,
            'reset_password' => $this->enablePasswordReset,
        )));
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
    }
}
