<?php

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * User controller.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 * @Route("/admin/user")
 */
class UserAdministrationController extends Controller {
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
	 * User list
	 *
	 * @Route("/", name="mapbender_user_list")
	 */
	public function indexAction() {
		$users = $this->em->find('MapbenderCoreBundle:User', '');
		print_r($users); die();
	}
}
