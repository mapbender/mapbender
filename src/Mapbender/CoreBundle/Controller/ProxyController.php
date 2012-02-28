<?php

namespace Mapbender\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Proxy controller.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 *
 * @Route("/proxy")
 */
class ProxyController extends Controller {
    /**
     * Open Proxy. Only checks if a valid session has been started earlier.
     * @Route("/open", name="mapbender_proxy_open")
     */
    public function openProxyAction() {
        $session = $this->get("session");
        if($session->get("proxyAllowed",false) !== true) {
            throw new AccessDeniedHttpException('You are not allowd to use this'
               . ' proxy without a session.');
        }
        session_write_close();

        return $this->get('mapbender.proxy')->proxy($this->getRequest());
    }
}

