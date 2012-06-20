<?php

namespace OwsProxy3\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OwsProxy3\CoreBundle\Component\Url;
use OwsProxy3\CoreBundle\Event\AfterProxyEvent;
use OwsProxy3\CoreBundle\Event\BeforeProxyEvent;
use Buzz\Browser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * WMS Proxy
 *
 * @author A.R.Pour
 */
class WmsProxy {
    protected $container;
    
    /**
     * @param Url $url 
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }
    
    /**
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function handle(Url $url) {
        $response = new Response();
        $browser = new Browser();
        
        $dispatcher = $this->container->get('event_dispatcher');
        
        try {
            $event = new BeforeProxyEvent($url);
            $dispatcher->dispatch('owsproxy.before_proxy', $event);
        } catch(\RuntimeException $e) {
            return;
        }
        
        $browserResponse = $browser->get( $url->toString() );
        
        if($browserResponse->isOk()) {
            $event = new AfterProxyEvent($url, $browserResponse);
            $dispatcher->dispatch('owsproxy.after_proxy', $event);
            
            // Set received headers to our response
            foreach($browserResponse->getHeaders() as $header) {
                if(strstr($header, ":") === false) continue;
                
                list($key, $val) = explode(":", $header, 2);
                //$response->headers->set($key, $val);
            }
            
            // Set received content to our response
            $response->setContent( $browserResponse->getContent() );

        } else {
            throw new \Exception("502 Bad Gateway");
        }

        return $response;
    }
}
