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
     * Open Proxy. Only checks if the desired URL is allowed
     * @Route("/open", name="mapbender_proxy_open")
     */
    public function openProxyAction() {
        $session = $this->get("session");
        
        if($session->get("proxyAllowed",false) !== true) {
            throw new AccessDeniedHttpException('You are not allowd to use this proxy without a session.');
        }
        
        session_write_close();
        
        $request = $this->get('request');

        $url = parse_url($request->get('url'));
        
        $baseUrl = $request->get('url');
        
        foreach($request->query->all() as $key => $value) {
            if($key === "url") continue;
            $baseUrl .= "&$key=".urlencode($value);
        }
        
        /*
        $proxyConf = $this->container->getParameter('proxy');

        $restrictPattern = sprintf('%s://%s', $url['scheme'], $url['host']);
        // Check if requested server is allowed and unrestricted
        if(array_key_exists($restrictPattern, $proxyConf['allowedhosts']) &&
            !$proxyConf['allowedhosts'][$restrictPattern]) {
        */
            // Only allow proxing HTTP and HTTPS
            if(!$url['scheme'] == 'http' && !$url['scheme'] == 'https') {
                throw new HttpException(500, 'This proxy only allow HTTP and HTTPS urls.');
            }

            $ch = curl_init($baseUrl);
            if($request->getMethod() == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                $contentType = explode(';', $request->headers->get('content-type'));
                if($contentType[0] == 'application/xml') {
                    $xml = file_get_contents('php://input');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-type: application/xml'
                    ));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
                }
            }
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

            // Get response from server
            //list($header, $content) = preg_split('/([\r\n][\r\n])\\1/', curl_exec($ch), 2);
            $content = curl_exec($ch);
            $status = curl_getinfo($ch);

            curl_close($ch);
            // Return server response
            $response = new Response();
            $response->setContent($content);
            $response->headers->set('Content-Type', $status['content_type']);
            return $response;
        /*
        } else {
            throw new HttpException(502, sprintf('This proxy does not allow you to access that location "%s"',
                $this->get('request')->get('url')));
        }
         */
    }

    /**
     * Secured Proxy. Checks if requested URL is allowed for current user
     * @Route("/secure", name="mapbender_proxy_secure")
     */
    public function secureProxyAction() {
        $url = $this->request->get('url');
    }
}

