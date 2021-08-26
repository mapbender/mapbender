<?php
namespace Mapbender\CoreBundle\Utils;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
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
        $parts = parse_url($url);
        $queries   = array();
        if (isset($parts["query"])) {
            parse_str($parts["query"], $queries);
        }
        $paramsToRemove = array_map('strtolower', array_merge($paramsToRemove, array_keys($paramsToAdd)));
        foreach ($queries as $key => $value) {
            if (in_array(strtolower($key), $paramsToRemove)) {
                unset($queries[$key]);
            }
        }
        $queries = array_replace($queries, $paramsToAdd);
        if (!empty($parts['port']) && !empty($parts['scheme'])) {
            // omit port if its the scheme's default
            $schemelower = strtolower($parts["scheme"]);
            if ($schemelower === 'http' && $parts['port'] == 80) {
                unset($parts['port']);
            } elseif ($schemelower === 'https' && $parts['port'] == 443) {
                unset($parts['port']);
            }
        }
        if ($queries) {
            $parts['query'] = http_build_query($queries);
        } else {
            unset($parts['query']);
        }
        return self::reconstructFromParts($parts);
    }

    /**
     * @param string $url
     * @param string $paramName
     * @param mixed $default
     * @return mixed
     * @throws \InvalidArgumentException on empty $paramName
     */
    public static function getQueryParameterCaseInsensitive($url, $paramName, $default = null)
    {
        if (!$paramName) {
            throw new \InvalidArgumentException("Empty parameter name");
        }
        $lcParamName = strtolower($paramName);
        $urlParams = array();
        parse_str(parse_url($url, PHP_URL_QUERY), $urlParams);
        foreach ($urlParams as $urlParam => $value) {
            if (strtolower($urlParam) == $lcParamName) {
                return $value;
            }
        }
        return $default;
    }

    /**
     * Inverse of parse_url.
     * This is a drop-in for a single-argument http_build_url as provided by the (rarely installed) "http" PECL
     * extension.
     *
     * @param string[] $parts
     * @return string
     */
    public static function reconstructFromParts($parts)
    {
        $urlOut = "";
        if (!empty($parts['scheme'])) {
            $urlOut .= "{$parts['scheme']}://";
        }
        if (!empty($parts['user'])) {
            $urlOut .= $parts['user'];
            if (!empty($parts['pass'])) {
                $urlOut .= ":{$parts['pass']}";
            }
            $urlOut .= "@";
        }
        if (!empty($parts['host'])) {
            $urlOut .= $parts['host'];
        }
        if (!empty($parts['port'])) {
            $urlOut .= ":{$parts['port']}";
        }
        if (!empty($parts['path'])) {
            $urlOut .= $parts['path'];
        }
        if (!empty($parts['query'])) {
            $urlOut .= "?{$parts['query']}";
        }
        if (!empty($parts['fragment'])) {
            $urlOut .= "#{$parts['fragment']}";
        }
        return $urlOut;
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
        if ($routerContext->getBaseUrl() && 0 === strpos($path, $routerContext->getBaseUrl())) {
            $path = '/' . ltrim(substr($path, strlen($routerContext->getBaseUrl())), '/');
        }
        try {
            return $matcher->match($path);
        } catch (ResourceNotFoundException $e) {
            // no match
            return null;
        }
    }

    /**
     * @param string $url
     * @param string|null $username
     * @param string|null $pass
     * @param bool $replace
     * @return string
     */
    public static function addCredentials($url, $username, $pass, $replace=true)
    {
        $parts = parse_url($url);
        $credentialsParts = array(
            'user' => rawurlencode($username),
            'pass' => rawurlencode($pass),
        );
        if ($replace) {
            $parts = array_replace($parts, $credentialsParts);
        } else {
            $parts = $parts + $credentialsParts;
        }
        return self::reconstructFromParts($parts);
    }
}
