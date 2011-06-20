<?php

namespace Mapbender\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Application controller.
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */
class ApplicationController extends Controller {
	/**
     * @Route("/application/{slug}", name="mapbender_application")
	 * @Template()
	 */
    public function applicationAction($slug) {
        //TODO: Check for ORM Applications first, YAML Application only come in
        //second place
        $application = $this->getYamlApplication($slug);
        return $application->render();
    }

    /**
     * Inflate an application from Yaml
     */
    private function getYamlApplication($slug) {
        // Try to load application configurations from parameters
        if(!$this->container->hasParameter('applications')) {
            throw new NotFoundHttpException('No applications are defined.');
        }
        $apps_pars = $this->container->getParameter('applications');

        // Find desired application configuration
        if(!array_key_exists($slug, $apps_pars)) {
            throw new NotFoundHttpException('Application ' . $slug . ' not found.');
        }

        // instantiate application
        return new Application($this->container, $apps_pars[$slug]);
    }
}
