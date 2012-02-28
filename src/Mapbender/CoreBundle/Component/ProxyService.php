<?php

/**
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */

namespace Mapbender\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProxyService {
    protected $proxy_conf;

    public function __construct($proxy_conf) {
        if($proxy_conf['host'] !== null) {
            $this->proxy_conf[CURLOPT_PROXY] = $proxy_conf['host'];
            $this->proxy_conf[CURLOPT_PROXYPORT] = $proxy_conf['port'];

            $user = $proxy_conf['user'];
            if($user && $proxy_conf['password']) {
                $user .= ':' . $proxy_conf['password'];
            }
            $this->proxy_conf[CURLOPT_PROXYUSERPWD] = $user;
        } else {
            $this->proxy_conf = array();
        }
    }

    /**
     * Proxy the given request
     *
     * @param Request $request
     * @return Response $response
     */
    public function proxy(Request $request) {
        $url = parse_url($request->get('url'));

        if(!$url) {
            throw new \RuntimeException('No URL passed in proxy request.');
        }

        $baseUrl = $request->get('url');

        foreach($request->query->all() as $key => $value) {
            if($key === "url") continue;
            $baseUrl .= "&$key=".urlencode($value);
        }

        // Only allow proxing HTTP and HTTPS
        if(!$url['scheme'] == 'http' && !$url['scheme'] == 'https') {
            throw new HttpException(500, 'This proxy only allow HTTP and '
                . 'HTTPS urls.');
        }

        // Init cUrl
        $ch = curl_init($baseUrl);

        // Add POST data if neccessary
        if($request->getMethod() == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            $contentType = explode(';', $request->headers->get('Content-Type'));

            if($contentType[0] == 'application/xml') {

                $xml = file_get_contents('php://input');
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-type: application/xml'));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
            }
        }

        $curl_config = array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER         => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT']);

        // Set params + proxy params
        curl_setopt_array($ch, $curl_config + $this->proxy_conf);

        // Get response from server
        $content = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);

        // Return server response
        return new Response($content, $status['http_code'], array(
            'Content-Type' => $status['content_type']));
    }
}

