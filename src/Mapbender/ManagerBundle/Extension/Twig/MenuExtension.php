<?php


namespace Mapbender\ManagerBundle\Extension\Twig;


use Mapbender\ManagerBundle\Component\Menu\MenuItem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension
{
    /** @var MenuItem[] */
    protected $items;
    /** @var bool */
    protected $initialized = false;


    /**
     * @param MenuItem[] $itemData
     * @param RequestStack $requestStack
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(protected $itemData, protected RequestStack $requestStack, protected AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public function getFunctions(): array
    {
        return [
            'mapbender_manager_menu_items' => new TwigFunction('mapbender_manager_menu_items', $this->mapbender_manager_menu_items(...)),
        ];
    }

    public function mapbender_manager_menu_items($legacyParamDummy = null)
    {
        return $this->getItems(true);
    }

    public function getDefaultRoute(): ?string
    {
        $items = $this->getItems(false);
        if (!$items) {
            throw new \RuntimeException("No manager routes defined");
        }
        return $items[0]->getRoute();
    }

    /**
     * @param bool $filterAccess
     * @return MenuItem[]
     */
    protected function getItems($filterAccess): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $items = [];
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
        $route = $this->requestStack->getCurrentRequest()->attributes->get('_route');
        foreach ($this->items as $item) {
            $item->checkActive($route);
        }

        $this->initialized = true;
    }
}
