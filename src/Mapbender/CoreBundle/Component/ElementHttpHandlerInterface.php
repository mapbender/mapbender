<?php


namespace Mapbender\CoreBundle\Component;


use Mapbender\CoreBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface for Mapbender Elements wishing to support http interactions with the client.
 * Default implementation is hard-baked into Component\Element.
 *
 * @deprecated switch to service type-elements ASAP for Symfony 4+ compatibility
 * @see \Mapbender\Component\Element\AbstractElementService
 * @see \Mapbender\Component\Element\HttpHandlerProvider
 * @see \Mapbender\Component\Element\ElementHttpHandlerInterface
 * @todo 3.3: remove this interface
 */
interface ElementHttpHandlerInterface
{
    /**
     * Should respond to the incoming http request.
     * $request currently has a guaranteed attribute 'action'; @see ApplicationController::elementAction.
     *
     * @param Request $request
     * @return Response
     */
    public function handleHttpRequest(Request $request);
}
