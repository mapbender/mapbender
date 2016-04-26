<?php

namespace Mapbender\Component;

class UrlHelper {
    public static function setParameters($url, array $parameters) {
        $query = parse_url($url, PHP_URL_QUERY);
        $fragment = parse_url($url, PHP_URL_FRAGMENT);

        $params = array();
        parse_str($query, $params);
        $params = array_merge($params, $parameters);
        $query = http_build_query($params);

        $q = strpos($url, '?');
        $newQueryFragment = '?' . $query . ($fragment ? '#' . $fragment : '');
        if($q !== False) {
            $url = substr_replace($url, $newQueryFragment, $q);
        } else {
            $url .= $newQueryFragment;
        }

        return $url;
    }
}
