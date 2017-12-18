<?php
namespace Mapbender\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The central proxy service which can be used internally or by the proxie
 * controllers.
 *
 * @deprecated Will be replaced by OWSProxy3
 * @author Christian Wygoda
 */
class ProxyService
{

    protected $proxy_conf;
    protected $noproxy;

    /**
     * ProxyService constructor.
     *
     * @param $proxy_conf
     */
    public function __construct($proxy_conf)
    {
        if($proxy_conf['host'] !== null)
        {
            $this->proxy_conf[CURLOPT_PROXY] = $proxy_conf['host'];
            $this->proxy_conf[CURLOPT_PROXYPORT] = $proxy_conf['port'];

            $user = $proxy_conf['user'];
            if($user && $proxy_conf['password'])
            {
                $user .= ':' . $proxy_conf['password'];
            }
            $this->proxy_conf[CURLOPT_PROXYUSERPWD] = $user;

            $this->noproxy = $proxy_conf['noproxy'];
        } else
        {
            $this->proxy_conf = array();
            $this->noproxy = array();
        }
    }

    /**
     * Proxy the given request
     *
     * @param Request $request
     * @return Response $response
     */
    public function proxy(Request $request)
    {
        $url = parse_url($request->get('url'));

        if(!$url)
        {
            throw new \RuntimeException('No URL passed in proxy request.');
        }

        $baseUrl = $request->get('url');

        foreach($request->query->all() as $key => $value)
        {
            if($key === "url")
                continue;
            $baseUrl .= "&$key=" . urlencode($value);
        }

        // Only allow proxing HTTP and HTTPS
        if(!isset($url['scheme']))
        {
            throw new HttpException(500, 'This proxy only allow HTTP and '
                    . 'HTTPS urls.');
        }
        if(!$url['scheme'] == 'http' && !$url['scheme'] == 'https')
        {
            throw new HttpException(500, 'This proxy only allow HTTP and '
                    . 'HTTPS urls.');
        }

        // Init cUrl
        $ch = curl_init($baseUrl);

        // Add POST data if neccessary
        if($request->getMethod() == 'POST')
        {
            curl_setopt($ch, CURLOPT_POST, true);
            $contentType = explode(';', $request->headers->get('Content-Type'));

            if($contentType[0] == 'application/xml')
            {
                $content = $request->getContent();
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: ' . $contentType[0]));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $request->getContent());
            } else
            {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $request->getParameters());
                //curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
            }
        }

        $user_agent = array_key_exists('HTTP_USER_AGENT', $_SERVER) ?
                $_SERVER['HTTP_USER_AGENT'] : 'Mapbender';

        $curl_config = array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => $user_agent);

        // Set params + proxy params if not noproxy
        if(!in_array($url['host'], $this->noproxy))
        {
            $curl_config += $this->proxy_conf;
        }

        curl_setopt_array($ch, $curl_config);

        // Get response from server
        $content = curl_exec($ch);

        $status = curl_getinfo($ch);

        if($content === false)
        {
            throw new \RuntimeException('Proxying failed: ' . curl_error($ch)
                    . ' [' . curl_errno($ch) . ']', curl_errno($ch));
        }
        curl_close($ch);
        // convert into content-type charset
        try
        {
            $contentType = $request->headers->get("content-type");
            if($contentType !== null && strlen($contentType) > 0)
            {
                $tmp = explode(";", $contentType);
                foreach($tmp as $value)
                {
                    if(stripos($value, "charset") !== false
                            && stripos($value, "charset") == 0)
                    {
                        $charset = explode("=", $value);
                    }
                }
                if(isset($charset) && isset($charset[1])
                        && !mb_check_encoding($content, $charset[1]))
                {
                    $content = mb_convert_encoding($content, $charset[1]);
                }
            }
        } catch(\Exception $e)
        {
            
        }
        // Return server response
        return new Response($content, $status['http_code'], array(
                    'Content-Type' => $status['content_type']));
    }
}
