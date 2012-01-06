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
        //TODO: Make the menu tree - and with it the ability to call menu
        //items - configurable in the bundle configuration
        $current_route = explode('_', $request->attributes->get('_route'));

        $menu = array(
            'apps' => array(
                'title' => 'Applications',
                '_controllers' => array(
                    'Application')),
            'layers' => array(
                'title' => 'Layers',
                '_controllers' => array(
                    'Layer',
                    'Repository')),
            'users' => array(
                'title' => 'Users',
                '_controllers' => array(
                    'User',
                    'Group')),
            'settings' => array(
                'title' => 'Settings',
                '_controllers' => array(
                    'Settings')));

        foreach($menu as &$item) {
            $item['active'] = false;
            foreach($item['_controllers'] as $controller) {
                if(strtolower($controller) === $current_route[2]) {
                    $item['active'] = true;
                }
            }
        }
        return array(
            'menu' => $menu);
    }
}

