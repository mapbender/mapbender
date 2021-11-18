<?php

namespace OwsProxy3\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Request;


/**
 * @author A.R.Pour
 * @author Paul Schmidt
 */
class ProxyQuery
{
    /** @var string */
    protected $url;

    /** @var string|null the POST content or null on GET requests */
    protected $content;

    /** @var array */
    protected $headers;

    /**
     * Factory method for ProxyQuery instances appropriate for GET request.
     * NOTE: DOES NOT deduplicate query params. Use Utils method if you need help
     * deduplicating params.
     * @see Utils::filterDuplicateQueryParams()
     *
     * @param string $url
     * @param string[] $headers
     * @return static
     * @since v3.1.6
     */
    public static function createGet($url, $headers = array())
    {
        // strip fragment and trailing query param separators
        $url = rtrim(preg_replace('/#.*$/', '', $url), '&?');
        return new static($url, null, $headers);
    }

    /**
     * Factory method for ProxyQuery instances appropriate for POST request.
     * NOTE: DOES NOT deduplicate query params. Use Utils method if you need help
     * deduplicating params.
     * @see Utils::filterDuplicateQueryParams()
     *
     * @param string $url
     * @param string[] $headers
     * @param string $content
     * @return static
     * @since v3.1.6
     */
    public static function createPost($url, $content, $headers = array())
    {
        // strip fragment and trailing query param separators
        $url = rtrim(preg_replace('/#.*$/', '', $url), '&?');
        // force $content to string
        return new static($url, $content ?: '', $headers);
    }

    /**
     * Creates an instance from a Symfony Request
     *
     * @param Request $request
     * @param string|null $forwardUrlParamName
     * @return static
     * @throws \InvalidArgumentException for invalid url
     */
    public static function createFromRequest(Request $request, $forwardUrlParamName = null)
    {
        if (!$forwardUrlParamName) {
            @trigger_error("Deprecated: " . __CLASS__ . '::' . __METHOD__ . ': expects explicit specification of "url" query parameter name', E_USER_DEPRECATED);
            $forwardUrlParamName = 'url';
        }
        $url = $request->query->get($forwardUrlParamName);
        $extraGetParams = $request->query->all();
        unset($extraGetParams[$forwardUrlParamName]);
        if ($extraGetParams) {
            $url = Utils::appendQueryParams($url, $extraGetParams);
        }
        // legacy quirk: filter repeated get params that differ only in case (first occurrence stays)
        $url = Utils::filterDuplicateQueryParams($url, false);
        $headers = Utils::getHeadersFromRequest($request);
        if ($request->getMethod() === 'POST') {
            return static::createPost($url, $request->getContent(), $headers);
        } else {
            return static::createGet($url, $headers);
        }
    }

    /**
     * @param string $url
     * @param string|null $content for POST
     * @param array $headers
     * @throws \InvalidArgumentException for empty url host
     */
    private function __construct($url, $content, $headers)
    {
        $parts = parse_url($url);
        if (empty($parts["host"])) {
            throw new \InvalidArgumentException("Missing host name");
        }
        $this->headers = array_replace($headers, array(
            'Host' => $parts['host'],
        ));
        $this->url = $url;
        $this->content = $content;
    }

    public function getHostname()
    {
        return \parse_url($this->url, PHP_URL_HOST);
    }

    /**
     * Returns the POST content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Returns the GET/POST method
     *
     * @return string
     */
    public function getMethod()
    {
        if ($this->content !== null) {
            return 'POST';
        } else {
            return 'GET';
        }
    }

    public function getUsername()
    {
        return rawurldecode(\parse_url($this->url, PHP_URL_USER) ?: '') ?: null;
    }

    public function getPassword()
    {
        if (\parse_url($this->url, PHP_URL_USER)) {
            return rawurldecode(\parse_url($this->url, PHP_URL_PASS) ?: '');
        } else {
            return null;
        }
    }

    /**
     * Returns the headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
