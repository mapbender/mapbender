<?php

/**
 * TODO: License
 */

namespace Mapbender\CoreBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Mapbender\CoreBundle\Entity\User;
use Mapbender\CoreBundle\Form\UserType;
use Symfony\Component\HttpFoundation\Request;
use Acme\HelloBundle\Mailer;

/**
 * User controller.
 *
 * @author Christian Wygoda
 * @author Paul Schmidt
 */
class UserController extends Controller {
    protected $em;

    /**
     * User login
     *
     * @Route("/user/login")
     * @Template()
     * @Method("GET")
     */
    public function loginAction() {
        $request = $this->get('request');
        if($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $request->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
        }

        return array(
            'last_username' => $request->getSession()->get(SecurityContext::LAST_USERNAME),
            'error' => $error,
            'selfregister' => $this->container->getParameter("mapbender.selfregister")
        );
    }

    /**
     * @Route("/user/login/check")
     */
    public function loginCheckAction() {
        //Don't worry, this is actually intercepted by the security layer.
    }

    /**
     * @Route("/user/logout")
     */
    public function logoutAction() {
        //Don't worry, this is actually intercepted by the security layer.
    }

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = NULL) {
        parent::setContainer($container);
        if($this->container === NULL) {
            throw \Exception('Mapbender\CoreBundle\Controller\UserController requires the container to be set.');
        }

        $this->em = $this->get('doctrine.orm.default_entity_manager');
    }


    /**
     * @Route("/")
     * @Secure("ROLE_USER")
     * @Template()
     */
    public function profileAction() {
        $user = $this->get('security.context')->getToken()->getUser();
        return array(
            'user' => $user,
        );
    }
}
