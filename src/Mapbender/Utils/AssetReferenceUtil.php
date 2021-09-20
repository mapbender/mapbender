<?php


namespace Mapbender\Utils;


class AssetReferenceUtil
{
    public static function isQualified($reference)
    {
        // Leading '@' => assume bundle-qualified assetic reference
        // Leading '/' or '.' => assume public resource reference
        return !!preg_match('#^[@/.]#', $reference);
    }

    /**
     * @param $scopeObject
     * @param $reference
     * @return string
     * @deprecated unqualified asset references will be an error on Mapbender 3.3
     */
    public static function qualify($scopeObject, $reference)
    {
        $bundleName = static::getBundleName($scopeObject);
        return "@{$bundleName}/Resources/public/{$reference}";
    }

    /**
     * Amend given bundle-implicit asset references with bundle scope from
     * given $scopeObject, so that assetic can resolve them. Passes through
     * already bundle-qualified references unmodified.
     *
     * If the passed reference is interpreted as a web-anchored file path (starts with '/')
     * or an app/Resources-relative path (starts with '.'), also return it unmodified.
     *
     * @param object $scopeObject
     * @param string[] $references
     * @param boolean $throwOnUnqualified
     * @return string[]
     * @deprecated unqualified asset references will be an error on Mapbender 3.3
     */
    public static function qualifyBulk($scopeObject, $references, $throwOnUnqualified)
    {
        $bundleName = null;
        $refsOut = array();
        foreach ($references as $ref) {
            if (!static::isQualified($ref)) {
                $bundleName = $bundleName ?: static::getBundleName($scopeObject);
                $message = "Missing explicit bundle path in asset reference "
                         . print_r($ref, true)
                         . " from " . get_class($scopeObject)
                         . "; this will be an error in Mapbender 3.3"
                ;
                if ($throwOnUnqualified) {
                    throw new \RuntimeException($message);
                } else {
                    @trigger_error("Deprecated: {$message}", E_USER_DEPRECATED);
                }

                $refsOut[] = "@{$bundleName}/Resources/public/{$ref}";
            } else {
                $refsOut[] = $ref;
            }
        }
        return $refsOut;
    }

    protected static function getBundleName($scopeObject)
    {
        if (!$scopeObject) {
            throw new \RuntimeException("Can't detect bundle name without scope object");
        }
        $fqcn = \get_class($scopeObject);
        return preg_replace('#^(\w+)\\\\(\w+).*$#', '$1$2', $fqcn);
    }
}
