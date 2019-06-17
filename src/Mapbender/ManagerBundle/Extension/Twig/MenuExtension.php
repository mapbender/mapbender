<?php


namespace Mapbender\ManagerBundle\Extension\Twig;


use Mapbender\ManagerBundle\Component\ManagerBundle;
use Mapbender\ManagerBundle\Component\Menu\LegacyItem;
use Mapbender\ManagerBundle\Component\Menu\MenuItem;
use Mapbender\ManagerBundle\Component\Menu\TopLevelItem;
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

    public function mapbender_manager_menu_items($legacyParamDummy = null)
    {
        return $this->getManagerControllersDefinition(true);
    }

    public function getDefaultRoute()
    {
        $controllers = $this->getManagerControllersDefinition(false);
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
     * @param bool $filterAccess
     * @return array
     */
    protected function getManagerControllersDefinition($filterAccess)
    {
        $routeDefinitions = array();
        foreach ($this->managerBundles as $bundle) {
            $bundleDefinitions = $bundle->getManagerControllers();
            foreach ($bundleDefinitions as $item) {
                if (is_array($item)) {
                    $item = LegacyItem::fromArray($item);
                }
                /** @var MenuItem $item */
                if (!$filterAccess || $item->filter($this->authorizationChecker)) {
                    $routeDefinitions[] = $item;
                }
            }
        }
        usort($routeDefinitions, function($a, $b) {
            /** @var TopLevelItem $a */
            /** @var TopLevelItem $b */
            $weightA = $a->getWeight();
            $weightB = $b->getWeight();
            if ($weightA == $weightB) {
                return 0;
            }

            return ($weightA < $weightB) ? -1 : 1;
        });
        return $routeDefinitions;
    }
}
