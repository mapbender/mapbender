<?php

/**
 * Mapbender layerset management
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */

namespace Mapbender\ManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/settings")
 */
class SettingsController extends Controller {
    /**
     * Renders the user list.
     *
     * @Route("/")
     * @Template
     */
    public function indexAction() {
        return array(
            'title' => 'Settings',
        );
    }
}

