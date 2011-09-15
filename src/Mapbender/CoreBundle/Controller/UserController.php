<?php

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * User controller.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 *
 * @Route("/user")
 */
class UserController extends Controller {
    protected $em;

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
     * User login
     *
     * @Route("/login")
     * @Template()
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
        );
    }

    /**
     * @Route("/login/check")
     */
    public function loginCheckAction() {
        //Don't worry, this is actually intercepted by the security layer.
    }

    /**
     * @Route("/logout")
     */
    public function logoutAction() {
        //Don't worry, this is actually intercepted by the security layer.
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

