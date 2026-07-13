<?php


namespace Mapbender\Component\Transformer;


use Mapbender\CoreBundle\Utils\UrlUtil;

/**
 * String transformer that targets scheme, hostname and port of a url.
 * Will never match / replace anything inside a query string or fragment.
 */
class BaseUrlTransformer implements OneWayTransformer
{
    protected StringReplaceTransformer $transformer;

    /**
     * @param string $from
     * @param string $to
     * @param bool $caseSensitive
     */
    public function __construct($from, $to, $caseSensitive = true)
    {
        $replacements = [
            $from => $to,
        ];
        $this->transformer = new StringReplaceTransformer($replacements, $caseSensitive);
    }

    public function process($x)
    {
        $parts = parse_url((string) $x);
        $baseUrlParts = [
            'scheme',
            'host',
            'port',
        ];
        $baseUrl = UrlUtil::reconstructFromParts(array_intersect_key($parts, array_flip($baseUrlParts)));

        $baseUrl = $this->transformer->process($baseUrl);
        $newParts = array_filter(parse_url((string) $baseUrl));
        $updatedParts = $newParts + $parts;
        if (empty($newParts['port'])) {
            unset($updatedParts['port']);
        }
        $reconstructed = UrlUtil::reconstructFromParts($updatedParts);
        // OGC service special: service URLs like to end in '?' or '&', which
        // is lost on reconstruction. If the original had this, restore it.
        $patterns = [
            '?' => '#\?$#',
            '&' => '#\?$#',
        ];
        foreach ($patterns as $suffix => $pattern) {
            if (preg_match($pattern, (string) $x) && !preg_match($pattern, $reconstructed)) {
                $reconstructed .= $suffix;
            }
        }
        if (!$reconstructed) {
            throw new \Exception("Hello " . var_export($x, true));
        }
        return $reconstructed;
    }
}
