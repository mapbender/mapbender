<?php

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * User controller.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 * 
 * @extra:Route("/user")
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
	 * @extra:Route("/login", name="mapbender_user_login")
	 * @extra:Template()
	 */
	public function loginAction() {
		$request = $this->get('request');
		if($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
			$error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
		} else {
			$error = $request->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
		}

		return array(
			'last_username' => $this->get('request')->getSession()->get(SecurityContext::LAST_USERNAME),
			'error' => $error,
		);
	}

	/**
	 * @extra:Route("/login_check", name="_security_check")
	 */
	public function loginCheckAction() {
		//Don't worry, this is actually intercepted by the security layer.
	}

	/**
	 * @extra:Route("/logout", name="mapbender_user_logout")
	 */
	public function logoutAction() {
		//Don't worry, this is actually intercepted by the security layer.
	}

	/**
	 * @extra:Route("/", name="mapbender_user_profile")
	 * @extra:Secure("ROLE_USER")
	 * @extra:Template()
	 */
	public function profileAction() {
		$user = $this->get('security.context')->getToken()->getUser();
		return array(
			'user' => $user,
		);
	}
}

