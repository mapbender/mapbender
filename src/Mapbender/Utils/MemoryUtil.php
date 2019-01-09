<?php


namespace Mapbender\Utils;


class MemoryUtil
{
    /**
     * Returns php memory_limit parsed into a megabyte value.
     * Returns null if memory is unlimited.
     * @return float|null
     */
    public static function getMemoryLimitMegs()
    {
        $memoryLimitStr = ini_get('memory_limit');
        return static::parseMemoryLimitMegs($memoryLimitStr);
    }

    /**
     * Parse a memory_limit-ish string ('128MB', '1G' etc) into a megabyte value
     * @param string $memoryLimitStr
     * @return float|null
     */
    public static function parseMemoryLimitMegs($memoryLimitStr)
    {
        if ($memoryLimitStr == '-1' || $memoryLimitStr == '0' || !strlen($memoryLimitStr)) {
            return null;
        } else {
            $suffix = substr($memoryLimitStr, -1);
            if (is_numeric($suffix)) {
                // bytes, no suffix
                return floatval($memoryLimitStr) / 1024 / 1024;
            }
            switch (strtoupper($suffix)) {
                case 'G':
                    return floatval(substr($memoryLimitStr, 0, -1)) * 1024;
                case 'M':
                    return floatval(substr($memoryLimitStr, 0, -1));
                case 'K':
                    return floatval(substr($memoryLimitStr, 0, -1)) / 1024;
                default:
                    throw new \UnexpectedValueException("Unrecognized memory limit suffix " . var_export($memoryLimitStr, true));
            }
        }
    }

    /**
     * Performs runtime increase of memory_limit. Given $target string should be in a valid php.ini format,
     * such as '1024M', '4G' etc.
     * Does nothing if the current memory limit is already above the requested $target.
     *
     * @param string $target
     */
    public static function increaseMemoryLimit($target)
    {
        $currentMegs = static::getMemoryLimitMegs();
        $targetMegs = static::parseMemoryLimitMegs($target);

        // NOTE: null means unlimited. There are multiple php.ini string representations of unlimited. We use '-1'.
        if ($targetMegs === null) {
            if ($currentMegs !== $targetMegs) {
                ini_set('memory_limit', '-1');
            }
        } elseif ($currentMegs !== null && $targetMegs > $currentMegs) {
            ini_set('memory_limit', "{$targetMegs}M");
        }
    }
}
