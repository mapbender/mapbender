<?php


namespace Mapbender\Component\Element;


use Mapbender\CoreBundle\Entity\Element;

/**
 * Simple base class for service-type elements
 * Methods left to implement for non-abstract child classes
 * * static getClassTitle() => string
 * * static getClassDescription() => string
 * * static getFormTemplate() => string or falsy
 * * static getType() => string (form type FQCN)
 * * static getDefaultConfiguration() => array
 * * getView(Element) => ElementView or falsy
 * * getRequiredAssets(Element) => array
 * * getWidgetName(Element) => string or falsy
 *
 * Optional overrides:
 * * getClientConfiguration(Element) => array (default: full configuration array from Element entity)
 * * getHttpHandler(Element) => ElementHttpHandlerInterface
 */
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
     * @param Element $element
     * @return ElementHttpHandlerInterface|null
     */
    public function getHttpHandler(Element $element)
    {
        return null;
    }
}
