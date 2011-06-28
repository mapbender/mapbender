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
        //TODO: Get ORM Applications, too
        $apps = $this->getYamlApplications();
        return array(
            'apps' => $apps
        );
    }

    private function getYamlApplications() {
        if(!$this->container->hasParameter('applications')) {
            return array();
        }

        $apps_parameters = $this->container->getParameter('applications');

        $apps = array();
        foreach($apps_parameters as $key => $conf) {
            $apps[$key] = array(
                'title' => $conf['title'],
                'type' => 'yaml');
        }

        return $apps;
    }
}
