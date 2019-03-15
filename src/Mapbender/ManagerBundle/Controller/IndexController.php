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
     * @param array[] $defs
     * @return array[]
     */
    protected function filterManagerControllerDefinitions($defs)
    {
        /** @var AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = $this->get('security.authorization_checker');
        $defsOut = array();
        foreach ($defs ?: array() as $k => $def) {
            if (!empty($def['enabled'])) {
                if ($def['enabled'] instanceof \Closure) {
                    $fn = $def['enabled'];
                    $enabled = $fn($authorizationChecker);
                } else {
                    throw new \RuntimeException("Unexpected type for 'enabled': " . (is_object($def['enabled']) ? get_class($def['enabled']) : gettype($def['enabled'])));
                }
                unset($def['enabled']);
            } else {
                $enabled = true;
            }
            if ($enabled) {
                if (!empty($def['subroutes'])) {
                    $def['subroutes'] = $this->filterManagerControllerDefinitions($def['subroutes']);
                }
                $defsOut[] = $def;
            }
        }
        return $defsOut;
    }

    /**
     * @return array
     */
    protected function getManagerControllersDefinition()
    {
        $routeDefinitions = array();
        foreach ($this->getManagerBundles() as $bundle) {
            $bundleDefinitions = $bundle->getManagerControllers();
            $bundleDefinitions = $this->filterManagerControllerDefinitions($bundleDefinitions);
            $routeDefinitions = array_merge($routeDefinitions, $bundleDefinitions);
        }
        usort($routeDefinitions, function($a, $b) {
            if($a['weight'] == $b['weight']) {
                return 0;
            }

            return ($a['weight'] < $b['weight']) ? -1 : 1;
        });

        if (!$routeDefinitions) {
            throw new \RuntimeException('No manager controllers registered');
        }

        return $routeDefinitions;
    }
}

