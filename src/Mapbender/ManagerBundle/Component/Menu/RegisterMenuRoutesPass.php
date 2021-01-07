<?php


namespace Mapbender\ManagerBundle\Component\Menu;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterMenuRoutesPass implements CompilerPassInterface
{
    /** @var MenuItem */
    protected $item;

    public function __construct(MenuItem $item)
    {
        $this->item = $item;
    }

    public function process(ContainerBuilder $container)
    {
        $key = 'mapbender.manager.menu.items';
        $items = $container->getParameter($key);
        $items[] = serialize($this->item);
        $container->setParameter($key, $items);
    }
}
