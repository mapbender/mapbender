<?php
namespace Mapbender\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * RequestListener
 */
class RequestListener {
    
    /**
     * 
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event) {
        $request = $event->getRequest();

        $session_id = $request->get(session_name());

        if($session_id) {
            $request->cookies->set(session_name(), $session_id);
            session_id($request->get(session_name()));
        }
    }
}

