<?php


namespace Mapbender\ManagerBundle\Extension\Twig;

use Mapbender\CoreBundle\Entity\Element;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ElementExtension extends AbstractExtension
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'mbmanager_element';
    }

    public function getFunctions()
    {
        return array(
            'is_map_element' => new TwigFunction('is_map_element', array($this, 'is_map_element')),
        );
    }

    /**
     * @param Element $element
     * @return bool
     */
    public function is_map_element(Element $element)
    {
        try {
            // @todo: Map class should be final
            return \is_a($element->getClass(), 'Mapbender\Component\Element\MainMapElementInterface', true);
        } catch (\ErrorException $e) {
            // thrown by debug mode class loader on Symfony 3.4+
            return false;
        }
    }
}
