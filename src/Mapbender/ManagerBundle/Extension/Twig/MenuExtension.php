<?php


namespace Mapbender\ManagerBundle\Extension\Twig;


use Mapbender\ManagerBundle\Component\ManagerBundle;
use Mapbender\ManagerBundle\Component\Menu\LegacyItem;
use Mapbender\ManagerBundle\Component\Menu\MenuItem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuExtension extends \Twig_Extension
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;
    /** @var MenuItem[] */
    protected $items;
    /** @var string[] (serialized items) */
    protected $itemData;
    /** @var bool */
    protected $initialized = false;
    /** @var array|null */
    protected $legacyInitArgs;


    /**
     * @param MenuItem[] $items
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param KernelInterface $kernel
     * @param string[] $legacyBundleNames
     * @param string[] $routePrefixBlacklist
     */
    public function __construct($items, AuthorizationCheckerInterface $authorizationChecker,
                                KernelInterface $kernel,
                                $legacyBundleNames,
                                $routePrefixBlacklist)
    {
        $this->itemData = $items;
        $this->authorizationChecker = $authorizationChecker;
        if ($legacyBundleNames) {
            $this->legacyInitArgs = array($kernel, $legacyBundleNames, $routePrefixBlacklist);
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
        return $this->getItems(true);
    }

    public function getDefaultRoute()
    {
        $controllers = $this->getItems(false);
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
    protected function getItems($filterAccess)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $items = array();
        foreach ($this->items as $item) {
            if (!$filterAccess || $item->filter($this->authorizationChecker)) {
                $items[] = $item;
            }
        }
        return $items;
    }

    protected function initialize()
    {
        $this->items = array_map('\unserialize', $this->itemData);
        if ($args = $this->legacyInitArgs) {
            $this->legacyInit($args[0], $args[1], $args[2]);
        }

        $this->initialized = true;
    }

    protected function legacyInit(KernelInterface $kernel, $bundleNames, $routePrefixBlacklist)
    {
        foreach ($bundleNames as $legacyBundleName) {
            /** @var ManagerBundle $bundle */
            $bundle = $kernel->getBundle($legacyBundleName);
            foreach ($bundle->getManagerControllers() as $topLevelMenuDefinition) {
                $item = LegacyItem::fromArray($topLevelMenuDefinition);
                if (MenuItem::filterBlacklistedRoutes(array($item), $routePrefixBlacklist)) {
                    $this->items[] = $item;
                }
            }
        }
        $this->items = MenuItem::sortItems($this->items);
    }
}
