<?php


namespace OwsProxy3\CoreBundle\Component;

/**
 * Curl-specific portion of HttpFoundationClient
 * @internal
 */
class CurlClientCommon extends BaseClient
{
    /**
     * @param string $hostName
     * @param array $config
     * @return array
     */
    public static function getCurlOptions($hostName, $config)
    {
        $options = array(
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_MAXREDIRS => 3,
        );
        if (isset($config['timeout'])) {
            $options[CURLOPT_TIMEOUT] = $config['timeout'];
        }
        if (isset($config['connecttimeout'])) {
            $options[CURLOPT_CONNECTTIMEOUT] = $config['connecttimeout'];
        }
        if (isset($config['checkssl'])) {
            $options[CURLOPT_SSL_VERIFYPEER] = !!$config['checkssl'];
        }
        if (isset($config['host']) && (empty($config['noproxy']) || !in_array($hostName, $config['noproxy']))) {
            $proxyOptions = array(
                CURLOPT_PROXY => $config['host'],
                CURLOPT_PROXYPORT => $config['port'],
            );
            if (isset($config['user']) && isset($config['password'])) {
                // must be encoded, at the very least to disambiguate embedded colon from separator colon
                // see https://curl.haxx.se/libcurl/c/CURLOPT_PROXYUSERPWD.html
                $proxyOptions = array_replace($proxyOptions, array(
                    CURLOPT_PROXYUSERPWD => rawurlencode($config['user']) . ':' . rawurlencode($config['password']),
                ));
            }
            $options = array_replace($options, $proxyOptions);
        }
        return $options;
    }

    protected static function flattenHeaders(array $values)
    {
        $flat = array();
        foreach ($values as $name => $value) {
            if (\is_numeric($name)) {
                $flat[] = $value;
            } elseif (\is_array($value)) {
                foreach ($value as $next) {
                    $flat[] = "{$name}: {$next}";
                }
            } elseif ($value !== null) {
                $flat[] = "{$name}: {$value}";
            }
        }
        return $flat;
    }

    /**
     * @param ProxyQuery $query
     * @return string[]
     */
    protected function prepareRequestHeaders(ProxyQuery $query)
    {
        $headers = Utils::filterHeaders($query->getHeaders(), array(
            "cookie",
            "user-agent",
            "content-length",
            "referer",
            "host",
        ));
        $headers['User-Agent'] = $this->userAgent;

        if ($query->getUsername()) {
            $headers['Authorization'] = 'Basic ' . \base64_encode($query->getUserName() . ':' . $query->getPassword());
        }
        return $headers;
    }

    /**
     * @param string $rawHeaders
     * @return string[]
     */
    protected static function parseResponseHeaders($rawHeaders)
    {
        $headers = array();
        foreach (\preg_split('#\\r?\\n#', $rawHeaders) as $i => $line) {
            $line = trim($line);
            if ($line) {
                if ($i === 0 && !\preg_match('#^[\w\d\-_]+:#', $line)) {
                    // = status line ~ "HTTP/1.1 200 OK"
                    continue;
                }
                $parts = \preg_split('#:\s*#', $line, 2);
                $headers[$parts[0]] = $parts[1];
            }
        }
        return $headers;
    }

}
