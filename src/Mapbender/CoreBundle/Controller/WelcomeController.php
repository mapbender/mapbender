<?php

namespace Mapbender\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Welcome controller.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
class WelcomeController extends Controller {
	/**
	 * @extra:Route("/", name="mapbender_welcome")
	 * @extra:Template()
	 */
	public function indexAction() {
		return array();
	}
}
