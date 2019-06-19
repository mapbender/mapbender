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

    public function serialize()
    {
        // force an error serializing closure
        \serialize($this->grantsCheckCallable);
        // if errors are suppressed, force the issue
        throw new \LogicException("Legacy menu items cannot be serialized because they contain closures");
    }

    public static function fromArray($values)
    {
        $withDefaults = $values + array(
            'enabled' => null,
            'route' => null,
        );
        // if there's now grants check closure, generate a regular MenuItem
        if (empty($withDefaults['enabled'])) {
            $instance = new MenuItem($values['title'], $values['route']);
        } else {
            $instance = new static($values['title'], $withDefaults['route'], $withDefaults['enabled']);
        }
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
