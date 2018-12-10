<?php
namespace Mapbender\CoreBundle\Utils;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Description of UrlUtil
 *
 * @author paul
 */
class UrlUtil
{

    /**
     * Validates an URL
     *
     * @param string $url URL
     * @param array $paramsToAdd  array key value paar to add to url
     * @param array $paramsToRemove  array of lower case parameter names to remove from url
     * @return string URL without parameter $paramName
     */
    public static function validateUrl($url, $paramsToAdd = array(), $paramsToRemove = array())
    {
        $rawUrl      = parse_url($url);
        $schemelower = strtolower($rawUrl["scheme"]);
        $newurl      = "";
        if ($schemelower === 'http' || $schemelower === 'https' || $schemelower === 'ftp') {
            $newurl = $rawUrl["scheme"] . "://" . $rawUrl['host'];
        } elseif ($schemelower === 'file') {
            $newurl = $rawUrl["scheme"] . ":///";
        } else {
            $newurl = $rawUrl["scheme"] . ":";
        }
        if (isset($rawUrl['user'])) {
            $newurl .= $rawUrl['user'] . ':' . (isset($rawUrl['pass']) ? $rawUrl['pass'] . "@" : "@");
        }
        if (isset($rawUrl['port']) && intval($rawUrl['port']) !== 80) {
            $newurl .= ':' . $rawUrl['port'];
        }
        if (isset($rawUrl['path']) && strlen($rawUrl['path']) > 0) {
            $newurl .= $rawUrl['path'];
        }
        $queries   = array();
        $getParams = array();
        if (isset($rawUrl["query"])) {
            parse_str($rawUrl["query"], $queries);
        }
        foreach ($getParams as $key => $value) {
            $queries[$key] = $value;
        }
        foreach ($paramsToAdd as $key => $value) {
            $queries[$key] = $value;
        }
        $help = array();
        foreach ($queries as $key => $value) {
            if (in_array(strtolower($key), $paramsToRemove)) {
                unset($queries[$key]);
            } else {
                $help[] = $key . "=" . $value;
            }
        }
        if (count($queries) > 0) {
            $newurl .= '?' . implode("&", $help);
        }
        return $newurl;
    }

    /**
     * Matches the given $url against configured routes and, on match, returns the routing
     * params (including extracted attributes, but also _route and _controller)
     * @see UrlMatcherInterface::match
     *
     * @param UrlMatcherInterface $matcher
     * @param string $url
     * @param bool $anyHost to require the same hostname as in the current request context
     * @return array|null
     */
    public static function routeParamsFromUrl(UrlMatcherInterface $matcher, $url, $anyHost = false)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $routerContext = $matcher->getContext();
        if (!$anyHost && $host && $host !== $routerContext->getHost()) {
            return null;
        }
        // To support installation in non-name-vhost / non-root configs, strip context base url first.
        // Context base commonly looks like ~'/somedir/mapender/local-fun-version/app_dev.php'
        if (0 === strpos($path, $routerContext->getBaseUrl())) {
            $path = '/' . ltrim(substr($path, strlen($routerContext->getBaseUrl())), '/');
        }
        try {
            return $matcher->match($path);
        } catch (ResourceNotFoundException $e) {
            // no match
            return null;
        }
    }
}
