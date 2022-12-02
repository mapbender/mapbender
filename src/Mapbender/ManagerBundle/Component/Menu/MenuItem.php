<?php


namespace Mapbender\ManagerBundle\Component\Menu;


use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @since v3.0.8.2
 */
class MenuItem implements \Serializable
{
    /** @var string */
    protected $title;
    /** @var string|null */
    protected $route;
    /** @var MenuItem[] */
    protected $children;
    /** @var int|null */
    protected $weight;
    /** @var bool */
    protected $current = false;
    /** @var bool */
    protected $active = false;

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

    public function __serialize()
    {
        $data = array(
            'title' => $this->title,
            'route' => $this->route,
        );
        $data += array_filter(array(
            'children' => $this->children,
        ));
        if ($this->weight !== null) {
            $data += array(
                'weight' => $this->weight,
            );
        }
        return $data;
    }

    public function serialize()
    {
        return \serialize($this->__serialize());
    }

    public function unserialize($serialized)
    {
        $data = \unserialize($serialized);
        $this->__unserialize($data);
    }

    public function __unserialize(array $data)
    {
        $this->title = $data['title'];
        $this->route = $data['route'];
        if (isset($data['weight'])) {
            $this->weight = $data['weight'];
        }
        if (isset($data['children'])) {
            $this->children = $data['children'];
        } else {
            $this->children = array();
        }
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
        return true;
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

    public function checkActive($route)
    {
        if ($this->route !== null && $route === $this->route) {
            $this->current = true;
            // Special snowflake FOMUserBundle uses the same route on a parent and child
            // entry...
            foreach ($this->children as $child) {
                $child->checkActive($route);
            }
            return true;
        } else {
            foreach ($this->children as $child) {
                if ($child->checkActive($route)) {
                    $this->active = true;
                    return true;
                }
            }
            return false;
        }
    }

    public function getActive()
    {
        return $this->current || $this->active;
    }

    public function getIsCurrent()
    {
        return $this->current;
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
