<?php


namespace Mapbender\ManagerBundle\Extension\Twig;

use Mapbender\Component\Element\MainMapElementInterface;
use Mapbender\CoreBundle\Entity\Element;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ElementExtension extends AbstractExtension
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'mbmanager_element';
    }

    public function getFunctions(): array
    {
        return [
            'is_map_element' => new TwigFunction('is_map_element', $this->is_map_element(...)),
        ];
    }

    /**
     * @param Element $element
     * @return bool
     */
    public function is_map_element(Element $element): bool
    {
        try {
            return \is_a($element->getClass(), MainMapElementInterface::class, true);
        } catch (\ErrorException) {
            // thrown by debug mode class loader on Symfony 3.4+
            return false;
        }
    }
}
