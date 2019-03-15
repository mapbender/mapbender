<?php

namespace Mapbender\ManagerBundle\Controller;

use FOM\ManagerBundle\Component\ManagerBundle;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Manager index controller.
 * Redirects to first menu item.
 * Provides menu via twig "render controller" construct.
 *
 * Copied into Mapbender from FOM v3.0.6.3
 * see https://github.com/mapbender/fom/blob/v3.0.6.3/src/FOM/ManagerBundle/Controller/ManagerController.php
 *
 * @author Christian Wygoda
 * @todo: render menu via twig extension + runtime https://symfony.com/doc/3.4/templating/twig_extension.html#creating-lazy-loaded-twig-extensions
 */
class IndexController extends Controller
{
    /**
     * Simply redirect to the applications list.
     *
     * @ManagerRoute("/", methods={"GET"})
     * @return Response
     */
    public function indexAction()
    {
        $controllers = $this->getManagerControllersDefinition();
        return $this->redirect($this->generateUrl($controllers[0]['route']));
    }

    /**
     * Renders the navigation menu
     *
     * @param Request $request
     * @return Response
     */
    public function menuAction(Request $request)
    {
        $current_route = $request->attributes->get('_route');
        $menu          = $this->getManagerControllersDefinition();

        $this->setActive($menu, $current_route);

        return $this->render('@MapbenderManager/Index/menu.html.twig', array(
            'menu' => $menu,
        ));
    }

    /**
     * @param $routes
     * @param $currentRoute
     * @return bool
     */
    private function setActive(&$routes, $currentRoute) {
        if(empty($routes)) return false;

        $return = false;

        foreach ($routes as &$route) {
            if($currentRoute === $route['route']) {
                $route['active'] = true;
                $return = true;
            }

            if(isset($route['subroutes']) && $this->setActive($route['subroutes'], $currentRoute)) {
                $route['active'] = true;
                $return = true;
            }
        }

        return $return;
    }

    /**
     * @param $container
     */
    protected function pruneSubroutes(&$container)
    {
        if(is_array($container) && array_key_exists('subroutes', $container)) {
            foreach($container['subroutes'] as $idx2 => &$route) {
                if(array_key_exists('enabled', $route)) {
                    $closure = $route['enabled'];
                    if(!$closure($this->get('security.authorization_checker'))) {
                        unset($container['subroutes'][$idx2]);
                        continue;
                    }
                }
                $this->pruneSubroutes($route);
            }
        }
    }

    /**
     * @return ManagerBundle[]
     */
    protected function getManagerBundles()
    {
        $bundles = array();
        foreach($this->get('kernel')->getBundles() as $bundle) {
            if(is_subclass_of($bundle, 'FOM\ManagerBundle\Component\ManagerBundle')) {
                $bundles[] = $bundle;
            }
        }
        return $bundles;
    }

    /**
     * @return array
     */
    protected function getManagerControllersDefinition()
    {
        /** @var AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = $this->get('security.authorization_checker');
        $manager_controllers = array();
        foreach ($this->getManagerBundles() as $bundle) {
            $controllers = $bundle->getManagerControllers();
            if ($controllers) {
                foreach ($controllers as $idx => &$controller) {
                    // Remove disabled main routes
                    if (array_key_exists('enabled', $controller)) {
                        $closure = $controller['enabled'];
                        if(!$closure($authorizationChecker)) {
                            unset($controllers[$idx]);
                            continue;
                        }
                    }
                    $this->pruneSubroutes($controllers[$idx]);
                }
                $manager_controllers = array_merge($manager_controllers, $controllers);
            }
        }

        usort($manager_controllers, function($a, $b) {
            if($a['weight'] == $b['weight']) {
                return 0;
            }

            return ($a['weight'] < $b['weight']) ? -1 : 1;
        });

        if(count($manager_controllers) === 0) {
            throw new \RuntimeException('No manager controllers registered');
        }

        return $manager_controllers;
    }
}

