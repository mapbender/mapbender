<?php

namespace Mapbender\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/*
 * Welcome controller.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
class WelcomeController extends Controller {
	public function indexAction() {
		return $this->render('MapbenderCoreBundle:Welcome:index.html.twig');
	}
}
