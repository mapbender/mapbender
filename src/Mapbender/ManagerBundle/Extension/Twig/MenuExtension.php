<?php


namespace Mapbender\ManagerBundle\Extension\Twig;


use Mapbender\ManagerBundle\Component\ManagerBundle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuExtension extends \Twig_Extension
{
    /** @var KernelInterface */
    protected $kernel;
    /** @var ManagerBundle[] */
    protected $managerBundles = array();
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /**
     * @param KernelInterface $kernel
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(KernelInterface $kernel, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->kernel = $kernel;
        $this->authorizationChecker = $authorizationChecker;
        foreach ($kernel->getBundles() as $bundle) {
            if ($bundle instanceof ManagerBundle) {
                $this->managerBundles[] = $bundle;
            }
        }
    }

    public function getFunctions()
    {
        return array(
            'mapbender_manager_menu_items' => new \Twig_SimpleFunction('mapbender_manager_menu_items', array($this, 'mapbender_manager_menu_items')),
        );
    }

    public function mapbender_manager_menu_items(Request $request)
    {
        $currentRoute = $request->attributes->get('_route');
        return $this->getManagerControllersDefinition($currentRoute);
    }

    public function getDefaultRoute()
    {
        $controllers = $this->getManagerControllersDefinition(null);
        if (!$controllers) {
            throw new \RuntimeException("No manager routes defined");
        }
        return $controllers[0]['route'];
    }

    /**
     * @param array $routeDefinition
     * @return array|false
     */
    protected function filterAccess($routeDefinition)
    {
        if (!empty($routeDefinition['enabled'])) {
            if ($routeDefinition['enabled'] instanceof \Closure) {
                $fn = $routeDefinition['enabled'];
                $enabled = $fn($this->authorizationChecker);
                if (!$enabled) {
                    return false;
                }
            } else {
                $type = is_object($routeDefinition['enabled']) ? get_class($routeDefinition['enabled']) : gettype($routeDefinition['enabled']);
                throw new \RuntimeException("Unexpected type for 'enabled': {$type}");
            }
        }
        unset($routeDefinition['enabled']);
        return $routeDefinition;
    }

    /**
     * @param array[] $defs
     * @param string|null $currentRoute
     * @return array[]
     */
    protected function filterManagerControllerDefinitions($defs, $currentRoute)
    {
        $defsOut = array();
        foreach ($defs ?: array() as $k => $def) {
            $def = $this->filterAccess($def);
            if (!$def) {
                continue;
            }
            $def['active'] = ($def['route'] === $currentRoute);
            if (!empty($def['subroutes'])) {
                $def['subroutes'] = $this->filterManagerControllerDefinitions($def['subroutes'], $currentRoute);
                if (!$def['active']) {
                    foreach ($def['subroutes'] as $sub) {
                        if (!empty($sub['active'])) {
                            $def['active'] = true;
                            break;
                        }
                    }
                }
            }
            // legacy menu.html.twig quirk: the template checks if 'active' is defined, not its boolean value
            if (!$def['active']) {
                unset($def['active']);
            }
            $defsOut[] = $def;
        }
        return $defsOut;
    }

    /**
     * @param string|null $currentRoute
     * @return array
     */
    protected function getManagerControllersDefinition($currentRoute)
    {
        $routeDefinitions = array();
        foreach ($this->managerBundles as $bundle) {
            $bundleDefinitions = $bundle->getManagerControllers();
            $bundleDefinitions = $this->filterManagerControllerDefinitions($bundleDefinitions, $currentRoute);
            $routeDefinitions = array_merge($routeDefinitions, $bundleDefinitions);
        }
        usort($routeDefinitions, function($a, $b) {
            if($a['weight'] == $b['weight']) {
                return 0;
            }

            return ($a['weight'] < $b['weight']) ? -1 : 1;
        });
        return $routeDefinitions;
    }
}
