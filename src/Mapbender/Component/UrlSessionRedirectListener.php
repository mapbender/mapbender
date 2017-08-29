<?php

namespace Mapbender\Component;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class UrlSessionRedirectListener
 *
 * @package    Mapbender\Component
 * @deprecated Remove it in 3.0.7. Nowhere used.
 */
class UrlSessionRedirectListener
{
    /** @var null|\Symfony\Component\HttpFoundation\Request */
    private $request;

    /**
     * UrlSessionRedirectListener constructor.
     *
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->request = $container->get('request_stack')->getCurrentRequest();
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $responseHeaderBag = $event->getResponse()->headers;
        $location          = $responseHeaderBag->get('location');
        if ($location) {
            $request  = $this->request;
            $location = UrlHelper::setParameters($location, array(
                session_name() => $request->getSession()->getId()
            ));
            $responseHeaderBag->set('location', $location);
        }
    }
}
