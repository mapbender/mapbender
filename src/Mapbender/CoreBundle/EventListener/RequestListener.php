<?php

namespace Mapbender\CoreBundle\EventListener;

use \Symfony\Component\HttpKernel\Event\GetResponseEvent;
use \Symfony\Component\HttpFoundation\Response;

class RequestListener {
    public function onKernelRequest(GetResponseEvent $event) {
        $request = $event->getRequest();

        $session_id = $request->get(session_name());

        if($session_id) {
            $request->cookies->set(session_name(), $session_id);
            session_id($request->get(session_name()));
//            print_r($request->cookies);die;
        }
    }
}

