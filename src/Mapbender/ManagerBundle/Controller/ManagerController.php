<?php

namespace Mapbender\ManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ManagerController extends Controller {
    /**
     * Simply redirect to the applications list.
     *
     * @Route("/")
     */
    public function indexAction() {
        return $this->redirect($this
            ->generateUrl('mapbender_manager_application_index'));
    }

    /**
     * Renders the navigation menu
     *
     * @Template
     */
    public function menuAction($request) {
        $current_route = $request->attributes->get('_route');

        $menu = $this->get('mapbender')->getAdminControllers();
        foreach($menu as &$item) {
            $item['active'] = false;
            foreach($item['controllers'] as $controller) {
                if(substr($current_route, 0, strlen($controller))
                    === $controller){
                    $item['active'] = true;
                }
            }
        }

        usort($menu, function($a, $b) {
            if ($a['weight'] == $b['weight']) {
                return 0;
            }
            return ($a['weight'] < $b['weight']) ? -1 : 1;
        });

        return array(
            'menu' => $menu);
    }
}

