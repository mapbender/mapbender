<?php

namespace Mapbender\CoreBundle\Utils;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
}
