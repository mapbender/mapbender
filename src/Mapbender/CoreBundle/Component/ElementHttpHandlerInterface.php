<?php


namespace Mapbender\CoreBundle\Component;


use Mapbender\CoreBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface for Mapbender Elements wishing to support http interactions with the client.
 * This is currently hard-baked into the base Component\Element class for continuity reasons, but it may
 * become optional at some point.
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
