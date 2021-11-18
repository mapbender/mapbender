<?php

namespace OwsProxy3\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Request;

/**
 * Utils class with help functions
 *
 * @author Paul Schmidt
 */
class Utils
{
    /**
     * Returns the headers from Request
     * 
     * @param Request $request
     * @return array
     */
    public static function getHeadersFromRequest(Request $request)
    {
        $headers = array();
        foreach ($request->headers->keys() as $key) {
            $value = $request->headers->get($key, null, true);
            if ($value !== null) {
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    /**
     * Returns a new array containing only the key => value pairs from $headers where the key
     * does not occur in $namesToRemove. Matching is case insensitive, because HTTP header names
     * are case insensitive.
     *
     * @param string[] $headers
     * @param string[] $namesToRemove
     * @return string[] remaining headers
     */
    public static function filterHeaders($headers, $namesToRemove)
    {
        $namesToRemove = array_map('strtolower', $namesToRemove);
        $filtered = array();
        foreach ($headers as $name => $value) {
            if (!\in_array(strtolower($name), $namesToRemove)) {
                $filtered[$name] = $value;
            }
        }
        return $filtered;
    }

    /**
     * Appends given $params to $url as (additional) query parameters.
     *
     * @param string $url
     * @param string[] $params
     * @return string
     * @since v3.1.6
     */
    public static function appendQueryParams($url, $params)
    {
        $fragmentParts = explode('#', $url, 2);
        if (count($fragmentParts) === 2) {
            return static::appendQueryParams($fragmentParts[0], $params) . '#' . $fragmentParts[1];
        }
        if ($params) {
            $dangle = preg_match('/[&?]$/', $url);
            $url = rtrim($url, '&?');
            $extraQuery = \http_build_query($params);
            if (preg_match('#\?#', $url)) {
                $url = "{$url}&{$extraQuery}";
            } else {
                $url = "{$url}?{$extraQuery}";
            }
            // restore dangling param separator, if input url had it
            if ($dangle) {
                $url .= '&';
            }
        }
        return $url;
    }

    /**
     * Remove repeated query params from given url and returns url with repeated params
     * removed. First occurence will remain.
     * NOTE: internal param separator chains will be contracted collaterally. E.g.
     *   "dog&&cat=hat" => "dog&cat=hat"
     *
     * @param string $url
     * @param boolean $caseSensitiveNames
     * @return string
     * @since v3.1.6
     */
    public static function filterDuplicateQueryParams($url, $caseSensitiveNames)
    {
        $fragmentParts = explode('#', $url, 2);
        if (count($fragmentParts) === 2) {
            return static::filterDuplicateQueryParams($fragmentParts[0], $caseSensitiveNames) . '#' . $fragmentParts[1];
        }
        $queryString = parse_url($url, PHP_URL_QUERY);
        $paramPairs = explode('&', $queryString);
        $paramPairsOut = array();
        foreach ($paramPairs as $pairIn) {
            if (!$pairIn || $pairIn == '?') {
                // internal chained param separators => strip them
                continue;
            }
            // NOTE: this will also support (and deduplicate) no-value params, e.g.
            // ?one&two&one
            $name = preg_replace('#[=].*$#', '', $pairIn);
            $dedupeKey = $caseSensitiveNames ? $name : strtolower($name);
            if (!array_key_exists($dedupeKey, $paramPairsOut)) {
                $paramPairsOut[$dedupeKey] = $pairIn;
            }
        }
        $dangle = preg_match('/[&?]$/', $url);
        $replacement = '?' . implode('&', $paramPairsOut);
        if ($dangle) {
            if ($paramPairsOut) {
                $replacement .= '&';
            }
        } else {
            $replacement = rtrim($replacement, '?');
        }
        return str_replace('?' . $queryString, $replacement, $url);
    }

    /**
     * Inject (or replace) given basic auth credentials into $url.
     *
     * @param string $url
     * @param string $user plain text (unencoded input)
     * @param string $password plain text (unencoded input)
     * @return string
     * @since v3.1.6
     */
    public static function addBasicAuthCredentials($url, $user, $password)
    {
        $fragmentParts = explode('#', $url, 2);
        if ($user && count($fragmentParts) === 2) {
            return static::addBasicAuthCredentials($fragmentParts[0], $user, $password) . '#' . $fragmentParts[1];
        }
        if ($user) {
            $credentialsEnc = implode(':', array(
                rawurlencode($user),
                rawurlencode($password ?: ''),
            ));
            return preg_replace('#(?<=//)([^@]+@)?#', $credentialsEnc . '@', $url, 1);
        } else {
            return $url;
        }
    }

    /**
     * Adds more key-value pairs from $params to given scalar POST content.
     * Returns null ONLY IF input $content is null and $params is empty.
     *
     * @param string|null $content
     * @param string[] $params
     * @return string|null
     * @since v3.1.6
     */
    public static function extendPostContent($content, $params)
    {
        if ($params) {
            return implode('&', array_filter(array(
                $content,
                \http_build_query($params),
            )));
        } else {
            return $content;
        }
    }
}
