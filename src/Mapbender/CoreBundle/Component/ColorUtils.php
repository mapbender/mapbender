<?php

namespace Mapbender\CoreBundle\Component;

class ColorUtils
{
    public static function addOpacityToColor(array $config, string $keyColor, string $keyOpacity, int $maxOpacity = 100): ?string
    {
        if (empty($config[$keyColor])) return null;
        $color = trim($config[$keyColor]);
        // 8-digit rgba hex string or rgba => just return the color, no need to include the opacity into it
        if (preg_match("/^#[0-9A-Fa-f]{8}$/", $color)) return $color;
        if (substr($color, 0, 4) === 'rgba') return $color;

        $opacity = floatval($config[$keyOpacity] / $maxOpacity);
        if ($opacity < 0) $opacity = 0;
        if ($opacity > 1) $opacity = 1;

        // rgb hex? convert to rgba
        if (preg_match("/^#[0-9A-Fa-f]{6}$/", $color)) {
            $r = hexdec(substr($color, 1, 2));
            $g = hexdec(substr($color, 3, 2));
            $b = hexdec(substr($color, 5, 2));
            return "rgba($r, $g, $b, $opacity)";
        }

        if (substr($color, 0, 3) === 'rgb') {
            return 'rgba' . substr($color, 3, -1) . ', ' . $opacity . ')';
        }

        return $config[$keyColor];
    }

    /**
     * @param string|null $hexRgbOrHsl
     * @return int[] with keys 'red', 'green', 'blue'
     */
    public static function parseColorToRgb(?string $hexRgbOrHsl): array
    {
        if (!$hexRgbOrHsl) return ['red' => 0, 'green' => 0, 'blue' => 0];

        if (str_starts_with($hexRgbOrHsl, 'rgb')) {
            // Handle rgb() format
            preg_match('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*/', $hexRgbOrHsl, $matches);
            return ['red' => (int)$matches[1], 'green' => (int)$matches[2], 'blue' => (int)$matches[3]];
        }

        if (str_starts_with($hexRgbOrHsl, 'hsl')) {
            return self::parseHslColor($hexRgbOrHsl);
        }

        if (str_starts_with($hexRgbOrHsl, '#')) {
            return self::parseHexColor($hexRgbOrHsl);
        }

        throw new \InvalidArgumentException("Unsupported color format: $hexRgbOrHsl");
    }

    public static function parseHexColor(string $hex): array
    {
        $hex = str_replace("#", "", $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return ['red' => $r, 'green' => $g, 'blue' => $b];
    }

    public static function parseHslColor($hsl): array
    {
        // Handle hsl() format
        preg_match('/hsla?\(\s*(\d+)\s*,\s*(\d+)%\s*,\s*(\d+)%\s*/', $hsl, $matches);
        $h = (int)$matches[1] / 360;
        $s = (int)$matches[2] / 100;
        $l = (int)$matches[3] / 100;

        // Convert HSL to RGB
        if ($s == 0) {
            $r = $g = $b = (int)($l * 255);
        } else {
            $q = ($l < 0.5) ? ($l * (1 + $s)) : ($l + $s - $l * $s);
            $p = 2 * $l - $q;
            $r = (int)(255 * self::hueToRgb($p, $q, $h + 1 / 3));
            $g = (int)(255 * self::hueToRgb($p, $q, $h));
            $b = (int)(255 * self::hueToRgb($p, $q, $h - 1 / 3));
        }
        return ['red' => $r, 'green' => $g, 'blue' => $b];
    }

    private static function hueToRgb(float|int $p, float|int $q, float|int $t)
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
        return $p;
    }

}
