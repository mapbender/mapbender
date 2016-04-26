<?php

namespace Mapbender\Component;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class UrlSessionRedirectListener {
    private $request;

    public function __construct($container) {
        $this->request = $container->get('request');
    }

    public function onKernelResponse(FilterResponseEvent $event) {
        $response = $event->getResponse();
        $location = $response->headers->get('location');
        if($location) {
            $location = UrlHelper::setParameters($location, array(
                session_name() => $this->request->getSession()->getId()));
            $response->headers->set('location', $location);
        }
    }
}
