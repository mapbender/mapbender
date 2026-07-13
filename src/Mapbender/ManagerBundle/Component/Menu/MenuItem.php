<?php


namespace Mapbender\ManagerBundle\Component\Menu;


use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuItem implements \Serializable
{
    /** @var MenuItem[] */
    protected array $children;
    protected ?int $weight = null;
    protected bool $current = false;
    protected bool $active = false;

    /**
     * @param string $title
     * @param string|null $route
     */
    public function __construct(
        protected string $title,
        protected ? string $route)
    {
        $this->children = [];
    }

    public function __serialize()
    {
        $data = [
            'title' => $this->title,
            'route' => $this->route,
        ];
        $data += array_filter([
            'children' => $this->children,
        ]);
        if ($this->weight !== null) {
            $data += [
                'weight' => $this->weight,
            ];
        }
        return $data;
    }

    public function serialize()
    {
        return \serialize($this->__serialize());
    }

    public function unserialize($data): void
    {
        $unserialized = \unserialize($data);
        $this->__unserialize($unserialized);
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
            $this->children = [];
        }
    }

    public static function create(string $title, ?string $route): self
    {
        return new static($title, $route);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getRoute(): ?string
    {
        return $this->route;
    }

    /**
     * @return MenuItem[]
     */
    public function getSubroutes(): array
    {
        return $this->children;
    }

    public function enabled(AuthorizationCheckerInterface $authorizationChecker): bool
    {
        return true;
    }

    /**
     * @param MenuItem[] $children
     * @return $this
     */
    public function addChildren($children): static
    {
        $this->children = array_merge($this->children, $children);
        return $this;
    }

    public function filter(AuthorizationCheckerInterface $authorizationChecker): bool
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

    public function filterRoute($prefix): bool
    {
        if (str_starts_with((string) $this->route, (string) $prefix)) {
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

    public function checkActive($route): bool
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

    public function getActive(): bool
    {
        return $this->current || $this->active;
    }

    public function getIsCurrent(): bool
    {
        return $this->current;
    }

    /**
     * @param $num
     * @return $this
     */
    public function setWeight($num): static
    {
        $this->weight = intval($num);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getWeight(): ?int
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
        usort($items, function($a, $b): int {
            /** @var MenuItem $a */
            /** @var MenuItem $b */
            $weightA = $a->getWeight();
            $weightB = $b->getWeight();
            return $weightA <=> $weightB;
        });
        return $items;
    }

    /**
     * Static filtering utility used by compiler and menu extension
     * @param MenuItem[] $items
     * @param string[] $routePrefixBlacklist
     * @return MenuItem[]
     */
    public static function filterBlacklistedRoutes(array $items, $routePrefixBlacklist): array
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
