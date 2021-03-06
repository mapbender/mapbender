<?php


namespace Mapbender\ManagerBundle\Component\Menu;


use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
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
    /** @var array[] */
    protected $requiredGrants = array();
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

    public function serialize()
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
        if ($this->requiredGrants) {
            $data['grants'] = array();
            foreach ($this->requiredGrants as $grantInfo) {
                if (\is_array($grantInfo)) {
                    /** @var ObjectIdentity $oid */
                    $oid = $grantInfo['oid'];
                    $data['grants'][] = $oid->getType() . ':' . implode(',', $grantInfo['attributes']);
                } else {
                    $data['grants'][] = $grantInfo;
                }
            }
        }
        return \serialize($data);
    }

    public function unserialize($serialized)
    {
        $data = \unserialize($serialized);
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
        if (isset($data['grants'])) {
            foreach ($data['grants'] as $grantSpec) {
                $parts = explode(':', $grantSpec);
                if (1 === count($parts)) {
                    $this->requireNamedGrant($parts[0]);
                } else {
                    $attributes = explode(',', $parts[1]);
                    $this->requireEntityGrant($parts[0], $attributes);
                }
            }
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
        foreach ($this->requiredGrants as $requiredGrant) {
            if (\is_array($requiredGrant)) {
                if (!$authorizationChecker->isGranted($requiredGrant['attributes'], $requiredGrant['oid'])) {
                    return false;
                }
            } else {
                if (!$authorizationChecker->isGranted($requiredGrant)) {
                    return false;
                }
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
     * @param string $name (e.g. 'ROLE_USER')
     * @return $this
     */
    public function requireNamedGrant($name)
    {
        $this->requiredGrants[] = $name;
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
