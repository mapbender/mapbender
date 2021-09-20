<?php


namespace Mapbender\Component\Element;

use Mapbender\CoreBundle\Entity\Element;

/**
 * Interface for service-type elements that serve http requests.
 * This interface only models retrieving a handler.
 *
 * For non-trivial http handling, it is expected that the http handler
 * is a separate service. This allows fewer service / parameter dependencies
 * on the main element service, drastically reducing overhead in other
 * scenarios.
 *
 * It's legal for the element service to implement the http
 * handling interface internally, and declare itself to be the http
 * handler.
 *
 * The Element entity is passed in to allow configuration inspection.
 * It's legal to return a constant handler / no handler or even different
 * handlers depending on Element properties.
 */
interface HttpHandlerProvider
{
    /**
     * Should return null if the element does not respond to any http requests.
     * Should return the handling service otherwise.
     *
     * NOTE: it's legal for the element service to implement the http
     * handling interface internally, and return itself here.
     *
     * @param Element $element
     * @return ElementHttpHandlerInterface|null
     */
    public function getHttpHandler(Element $element);
}
