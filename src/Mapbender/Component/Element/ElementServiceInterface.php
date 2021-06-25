<?php


namespace Mapbender\Component\Element;


use Mapbender\CoreBundle\Component\ElementBase\EditableInterface;

/**
 * Base interface for service-type elements
 * Methods left to implement for non-abstract child classes
 * * static getClassTitle() => string
 * * static getClassDescription() => string
 * * static getFormTemplate() => string or falsy
 * * static getType() => string (form type FQCN)
 * * static getDefaultConfiguration() => array
 * * getView(Element) => ElementView or falsy
 * * getRequiredAssets(Element) => array
 * * getWidgetName(Element) => string or falsy
 * * getClientConfiguration(Element) => array
 */
interface ElementServiceInterface extends ElementServiceFrontendInterface, EditableInterface
{
}
