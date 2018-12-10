<?php

namespace Mapbender\PrintBundle\Component\Plugin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface for server-side extensions to the PrintClient Element's http interface
 * The handleRequest implementation should return null (*not* throw http exceptions) if the requested action is not
 * recognized by the plugin instance. This allows other plugins (or the host Element) to continue searching for handlers.
 */
interface PrintClientHttpPluginInterface extends PluginBaseInterface, \Mapbender\CoreBundle\Component\ElementHttpHandlerInterface
{
    /**
     * Should respond to the incoming http request.
     * $request currently has a guaranteed attribute 'action'; @see ApplicationController::elementAction.
     * Different to base interface, implementations of this method should return null if the plugin is not interested
     * in handling the request.
     *
     * @param Request $request
     * @return Response|null
     */
    public function handleHttpRequest(Request $request);
}
