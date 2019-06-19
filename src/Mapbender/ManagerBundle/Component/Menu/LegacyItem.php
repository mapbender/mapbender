<?php


namespace Mapbender\ManagerBundle\Component\Menu;


use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LegacyItem extends MenuItem
{
    /** @var \Closure|null */
    protected $grantsCheckCallable;

    public function __construct($title, $route, $grantsCheckCallable)
    {
        parent::__construct($title, $route);
        $this->grantsCheckCallable = $grantsCheckCallable;
    }

    public static function fromArray($values)
    {
        $withDefaults = $values + array(
            'enabled' => null,
            'route' => null,
        );
        $instance = new static($values['title'], $withDefaults['route'], $withDefaults['enabled']);
        if (array_key_exists('weight', $values)) {
            $instance->setWeight($values['weight']);
        }
        if (!empty($values['subroutes'])) {
            $children = array();
            foreach ($values['subroutes'] as $childDef) {
                $children[] = static::fromArray($childDef);
            }
            $instance->addChildren($children);
        }
        return $instance;
    }

    public function enabled(AuthorizationCheckerInterface $authorizationChecker)
    {
        if ($this->grantsCheckCallable) {
            $fn = $this->grantsCheckCallable;
            return $fn($authorizationChecker);
        }
        return parent::enabled($authorizationChecker);
    }
}
