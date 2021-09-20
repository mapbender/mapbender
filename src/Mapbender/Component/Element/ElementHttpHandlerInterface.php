<?php


namespace Mapbender\Component\Element;


use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;


/**
 * Interface for service-type elements that want to serve http requests.
 * This interface prescribes the actual request handling method, and
 * may be implemented by either the element service itself, or (recommended)
 * by a separate class.
 */
interface ElementHttpHandlerInterface
{
    /**
     * @param Element $element
     * @param Request $request
     * @return Response
     * @throws HttpException
     */
    public function handleRequest(Element $element, Request $request);
}
