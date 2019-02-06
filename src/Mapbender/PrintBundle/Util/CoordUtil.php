<?php

namespace Mapbender\PrintBundle\Util;

class CoordUtil
{
    /**
     * Interpolate between given vectors. Inputs can have any dimensionality,
     * but must use common keys. Return value will also have the same keys.
     *
     * @param float[] $start
     * @param float[] $end
     * @param float $ratio [0;1] for results between start and end
     * @return float[]
     */
    public static function interpolateLinear($start, $end, $ratio)
    {
        $vOut = array();
        foreach ($start as $key => $startElement) {
            $vOut[$key] = $startElement + ($end[$key] - $startElement) * $ratio;
        }
        return $vOut;
    }

    /**
     * Calculate distance between two vectors, simple Pythagoras method.
     * Expect to be surprised if inputs have zero or differing number of elements.
     *
     * @param float[] $a
     * @param float[] $b
     * @return float
     */
    public static function distance($a, $b)
    {
        $sumOfSquares = 0;
        foreach ($a as $key => $aElement) {
            $elementDifference = $b[$key] - $aElement;
            $sumOfSquares += $elementDifference * $elementDifference;
        }
        return sqrt($sumOfSquares);
    }
}
