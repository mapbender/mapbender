<?php


namespace Mapbender\Component\Element;


use Mapbender\CoreBundle\Entity\Element;

abstract class AbstractElementService
    implements ElementServiceInterface, HttpHandlerProvider

{
    /**
     * @param Element $element
     * @return array
     */
    public function getClientConfiguration(Element $element)
    {
        return $element->getConfiguration() ?: array();
    }

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
     */
    public function getRequiredAssets(Element $element)
    {
        return array();
    }

    /**
     * @param Element $element
     * @return ElementHttpHandlerInterface|null
     */
    public function getHttpHandler(Element $element)
    {
        return null;
    }
}
