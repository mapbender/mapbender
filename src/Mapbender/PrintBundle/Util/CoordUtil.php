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

    public static function getRingCentroid($points)
    {
        $nPoints = count($points);
        if ($nPoints && $points[$nPoints - 1] === $points[0]) {
            $points = array_slice($points, 0, -1);
            --$nPoints;
        }
        if ($nPoints <= 2) {
            return $points[0];
        }
        // Tesselate into triangles, calculate sum of centers of triangles weighted by
        // signed area.
        // See https://gis.stackexchange.com/a/164270
        $weights = array();
        $sums = array(0, 0);
        for ($i = 1; $i < $nPoints - 1; ++$i) {
            $tri = array(
                $points[0],
                $points[$i],
                $points[$i + 1],
            );
            $triangleCenter = static::getAverage($tri);
            $signedArea = ($tri[1][0] - $tri[0][0]) * ($tri[2][1] - $tri[0][1])
                        - ($tri[2][0] - $tri[0][0]) * ($tri[1][1] - $tri[0][1])
            ;
            $sums[0] += $signedArea * $triangleCenter[0];
            $sums[1] += $signedArea * $triangleCenter[1];
            $weights[] = $signedArea;
        }
        $scale = 1.0 / array_sum($weights);
        return array(
            $scale * $sums[0],
            $scale * $sums[1],
        );
    }

    /**
     * @param float[][] $points
     * @return float[]
     */
    public static function getAverage($points)
    {
        $sums = array(0.0, 0.0);
        $nUsed = 0;
        foreach ($points as $i => $point) {
            if (!$i || $point !== $points[0]) {
                $sums[0] += $point[0];
                $sums[1] += $point[1];
                ++$nUsed;
            }
        }
        $weight = 1.0 / $nUsed;
        return array(
            $sums[0] * $weight,
            $sums[1] * $weight,
        );
    }
}
