<?php


namespace Mapbender\CoreBundle\Utils;

use Symfony\Component\HttpFoundation\Request;

/**
 * Utility class bundling functions related to Symfony Request objects
 * @package Mapbender\CoreBundle\Utils
 */
class RequestUtil
{
    /**
     * Extract the value (or $default if missing) of GET parameter with given $paramName from given Request, ignoring
     * parameter name case.
     *
     * @param Request $request
     * @param $paramName
     * @param mixed $default
     * @return mixed
     */
    public static function getGetParamCaseInsensitive(Request $request, $paramName, $default=null)
    {
        $allGetParams = $request->query->all();
        return ArrayUtil::getDefaultCaseInsensitive($allGetParams, $paramName, $default);
    }
}
