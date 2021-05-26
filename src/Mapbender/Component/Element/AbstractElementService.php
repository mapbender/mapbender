<?php


namespace Mapbender\Component\Element;


use Mapbender\CoreBundle\Component\ElementBase\EditableInterface;
use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Entity\Element;

abstract class AbstractElementService implements MinimalInterface, EditableInterface
{
    /**
     * @param Element $element
     * @return ElementView
     */
    abstract public function getView(Element $element);

    /**
     * @param Element $element
     * @return array
     */
    public function getClientConfiguration(Element $element)
    {
        return $element->getConfiguration() ?: array();
    }

    /**
     * Should return the (namespaced) JavaScript widget constructor name. E.g. 'mapbender.mbAboutDialog'.
     * May also return boolean false to indicate no javascript logic needs initializing at all.
     *
     * @param Element $element
     * @return string|false
     */
    abstract public function getWidgetName(Element $element);

    /**
     * Get lists of paths to required assets.
     *
     * Return should be a 2D array with top-level keys 'js', 'css' and 'trans'.
     * Each sub-array lists the required assets.
     *
     * Use bundle-style paths ('@MapbenderSomethingBundle/Resources/public/...') to reference assets from a different
     * bundle. Use absolute paths ('/components/something/...') to reference a file in the web folder.
     *
     * @param Element $element
     * @return string[][]
     * @noinspection PhpUnusedParameterInspection
     */
    public function getRequiredAssets(Element $element)
    {
        return array();
    }
}
