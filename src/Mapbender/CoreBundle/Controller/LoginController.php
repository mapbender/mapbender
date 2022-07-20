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
     * @Route("/user/login/check", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $form = $this->formFactory->create('Mapbender\CoreBundle\Form\Type\LoginType', null, array(
            'action' => 'login/check',
        ));

        if ($request->getMethod() === 'GET') {
            $error = $this->authUtils->getLastAuthenticationError(true);
            $lastUsername = $this->authUtils->getLastUsername();
            $form->get('_username')->setData($lastUsername);
            if ($error) {
                $form->addError(new FormError($error->getMessage()));
            }
        }

        return new Response($this->templateEngine->render('@MapbenderCore/Login/login.html.twig', array(
            'form' => $form->createView(),
            'selfregister' => $this->enableRegistration,
            'reset_password' => $this->enablePasswordReset,
        )));
    }

    /**
     * @Route("/user/logout")
     */
    public function logoutAction()
    {
        //Don't worry, this is actually intercepted by the security layer.
    }
}
