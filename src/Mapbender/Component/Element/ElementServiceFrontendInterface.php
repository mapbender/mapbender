<?php


namespace Mapbender\Component\Element;


use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Entity\Element;

interface ElementServiceFrontendInterface extends MinimalInterface
{
    /**
     * Should return an ElementView instance (TemplateView or StaticView)
     * modelling / containing frontend markup, and any required attributes
     * (css classes etc) on containing tag.
     *
     * @param Element $element
     * @return ElementView|false
     */
    public function getView(Element $element);

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
    public function getRequiredAssets(Element $element);

    /**
     * Should return the (namespaced) JavaScript widget constructor name. E.g. 'mapbender.mbAboutDialog'.
     * May also return boolean false to indicate no javascript logic needs initializing at all.
     *
     * @param Element $element
     * @return string|false
     */
    public function getWidgetName(Element $element);

    /**
     * Should return the configuration array passed to the client-side JavaScript
     * widget constructor.
     * @see getWidgetName
     * Should never contain sensitive information (connection / http passwords etc).
     * All values are visible via /application/<slug>/config url.
     *
     * @param Element $element
     * @return array
     */
    public function getClientConfiguration(Element $element);
}
