<?php


namespace Mapbender\ManagerBundle\Component\Menu;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuItem
{
    /** @var string */
    protected $title;
    /** @var string|null */
    protected $route;
    /** @var MenuItem[] */
    protected $children;
    /** @var int|null */
    protected $weight;
    /** @var array[] */
    protected $requiredGrants = array();

    /**
     * @param string $title
     * @param string|null $route
     */
    public function __construct($title, $route)
    {
        $this->title = $title;
        $this->route = $route;
        $this->children = array();
    }

    /**
     * @param $title
     * @param $route
     * @return static
     */
    public static function create($title, $route)
    {
        return new static($title, $route);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return MenuItem[]
     */
    public function getSubroutes()
    {
        return $this->children;
    }

    public function enabled(AuthorizationCheckerInterface $authorizationChecker)
    {
        foreach ($this->requiredGrants as $requiredGrant) {
            if (!$authorizationChecker->isGranted($requiredGrant['attributes'], $requiredGrant['oid'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $className
     * @param $attributes
     * @return $this
     */
    public function requireEntityGrant($className, $attributes)
    {
        $this->requiredGrants[] = array(
            'oid' => new ObjectIdentity('class', $className),
            'attributes' => (array)$attributes,
        );
        return $this;
    }

    /**
     * @param MenuItem[] $children
     * @return $this
     */
    public function addChildren($children)
    {
        $this->children = array_merge($this->children, $children);
        return $this;
    }

    public function filter(AuthorizationCheckerInterface $authorizationChecker)
    {
        if (!$this->enabled($authorizationChecker)) {
            return false;
        } else {
            foreach ($this->children as $index => $child) {
                if (!$child->filter($authorizationChecker)) {
                    unset($this->children[$index]);
                }
            }
            return true;
        }
    }

    public function filterRoute($prefix)
    {
        if (0 === strpos($this->route, $prefix)) {
            return false;
        } else {
            foreach ($this->children as $index => $child) {
                if (!$child->filterRoute($prefix)) {
                    unset($this->children[$index]);
                }
            }
            return true;
        }
    }

    public function isCurrent(Request $request)
    {
        $route = $request->attributes->get('_route');
        return $this->route !== null && $route === $this->route;
    }

    public function getActive(Request $request)
    {
        if ($this->isCurrent($request)) {
            return true;
        } else {
            foreach ($this->children as $child) {
                if ($child->getActive($request)) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * @param $num
     * @return $this
     */
    public function setWeight($num)
    {
        $this->weight = intval($num);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Static sorting utility used by compiler and menu extension
     * @param MenuItem[] $items
     * @return MenuItem[]
     */
    public static function sortItems($items)
    {
        usort($items, function($a, $b) {
            /** @var MenuItem $a */
            /** @var MenuItem $b */
            $weightA = $a->getWeight();
            $weightB = $b->getWeight();
            if ($weightA == $weightB) {
                return 0;
            }

            return ($weightA < $weightB) ? -1 : 1;
        });
        return $items;
    }

    /**
     * Static filtering utility used by compiler and menu extension
     * @param MenuItem[] $items
     * @param string[] $routePrefixBlacklist
     * @return MenuItem[]
     */
    public static function filterBlacklistedRoutes($items, $routePrefixBlacklist)
    {
        foreach ($items as $index => $item) {
            foreach ($routePrefixBlacklist as $prefix) {
                if (!$item->filterRoute($prefix)) {
                    unset($items[$index]);
                    break;
                }
            }
        }
        return $items;
    }
}
