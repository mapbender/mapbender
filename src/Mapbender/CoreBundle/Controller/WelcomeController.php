<?php

namespace Mapbender\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Welcome controller.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
class WelcomeController extends Controller {
	/**
	 * @Route("/", name="mapbender_welcome")
	 * @Template()
	 */
	public function indexAction() {
		return array();
	}
}
