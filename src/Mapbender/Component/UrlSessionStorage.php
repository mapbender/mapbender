<?php

namespace Mapbender\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\SessionStorage\NativeSessionStorage;

class UrlSessionStorage extends NativeSessionStorage {
    private $request;
    public function __construct(array $options, ContainerInterface $container) {
        $request = $container->get('request');
        $this->request = $request;

        $session_id = $request->get(session_name());
        $request->cookies->set(session_name(), $session_id);

        if ($session_id) {
            //session_id($session_id);
       }

        return parent::__construct($options);
    }
}

