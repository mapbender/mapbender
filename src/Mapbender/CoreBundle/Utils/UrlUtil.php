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
     * @param string $url input
     * @param string $to new host name
     * @param string|null $from old host name (optional); if given, only replace if hostname in $url equals $from
     * @return string updated $url (or unchanged $url if mismatching $from given)
     */
    public static function replaceHost($url, $to, $from = null)
    {
        $parts = parse_url($url);
        if (empty($parts['host'])) {
            /**
             * @todo: this should probably an exception; unfortunately, we have bad data in certain production dbs...
             */
            return $url;
        }
        if ($from && $from != $parts['host']) {
            $urlOut = $url;
        } else {
            $parts['host'] = $to;
            $urlOut = static::reconstruct($parts);
        }
        return $urlOut;
    }

    /**
     * Inverse of parse_url.
     * This should be a drop-in for http_build_url provided by the (rarely installed) "http" PECL extension.
     *
     * @param string[] $parts
     * @return string
     */
    public static function reconstruct($parts)
    {
        $urlOut = "";
        if (!empty($parts['scheme'])) {
            if ($parts['scheme'] == 'file') {
                $urlOut .= "{$parts['scheme']}:///";
            } else {
                $urlOut .= "{$parts['scheme']}://";
            }
        }
        if (!empty($parts['user'])) {
            $urlOut .= $parts['user'];
        }
        if (!empty($parts['pass'])) {
            $urlOut .= ":{$parts['pass']}";
        }
        if (!empty($parts['user']) || !empty($parts['pass'])) {
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
     * Add more GET params onto the given $baseUrl (assumed to be already well-formed)
     *
     * @param string $baseUrl
     * @param mixed[] $params
     * @return string
     */
    public static function appendQueryParams($baseUrl, $params)
    {
        $formattedParams = array();
        $addSingleParam = function(&$target, $k, $v) use (&$addSingleParam) {
            if (is_array($v)) {
                // we support repeats of the same param name
                foreach ($v as $subValue) {
                    $addSingleParam($target, $k, $subValue);
                }
            } else if ($v) {
                $target[] = rawurlencode($k) . '=' . rawurlencode($v);
            }
        };
        foreach ($params as $paramKey => $paramValue) {
            $addSingleParam($formattedParams, $paramKey, $paramValue);
        }
        if ($formattedParams) {
            $finalUrl = rtrim($baseUrl, '&?');
            if (false === strpos($finalUrl, '?')) {
                $finalUrl .= '?';
            } else {
                $finalUrl .= '&';
            }
            $finalUrl .= implode('&', $formattedParams);
        } else {
            $finalUrl = $baseUrl;
        }
        return $finalUrl;
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
