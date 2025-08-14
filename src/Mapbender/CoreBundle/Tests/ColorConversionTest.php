<?php

namespace Mapbender\CoreBundle\Tests;

use Mapbender\CoreBundle\Component\ColorUtils;
use PHPUnit\Framework\TestCase;

class ColorConversionTest extends TestCase
{
    public function testAddOpacityToColor() {
        $this->assertEquals('rgba(0, 0, 0, 0)', ColorUtils::addOpacityToColor([
            'color' => 'rgb(0, 0, 0)',
            'opacity' => 0,
        ], 'color', 'opacity'));

        $this->assertEquals('rgba(0, 0, 0, 1)', ColorUtils::addOpacityToColor([
            'color' => 'rgb(0, 0, 0)',
            'opacity' => 100,
        ], 'color', 'opacity'));

        $this->assertEquals('rgba(0, 0, 0, 0.5)', ColorUtils::addOpacityToColor([
            'color' => 'rgb(0, 0, 0)',
            'opacity' => 50,
        ], 'color', 'opacity'));

        // rgba should stay the same
        $this->assertEquals('rgba(0, 0, 0, 0.5)', ColorUtils::addOpacityToColor([
            'color' => 'rgba(0, 0, 0, 0.5)',
            'opacity' => 66,
        ], 'color', 'opacity'));

        // 6 digit hex codes should be converted to rgba
        $this->assertEquals('rgba(0, 0, 0, 0)', ColorUtils::addOpacityToColor([
            'color' => '#000000',
            'opacity' => 0,
        ], 'color', 'opacity'));

        $this->assertEquals('rgba(0, 0, 0, 1)', ColorUtils::addOpacityToColor([
            'color' => '#000000',
            'opacity' => 100,
        ], 'color', 'opacity'));

        $this->assertEquals('rgba(0, 0, 0, 0.5)', ColorUtils::addOpacityToColor([
            'color' => '#000000',
            'opacity' => 50,
        ], 'color', 'opacity'));

        // 8 digit hex codes can stay as they are
        $this->assertEquals('#0000007f', ColorUtils::addOpacityToColor([
            'color' => '#0000007f',
            'opacity' => 66,
        ], 'color', 'opacity'));
    }

    public function testColorParsing()
    {
        $this->assertEquals([
            'red' => 0,
            'green' => 0,
            'blue' => 0
        ], ColorUtils::parseColorToRgb('rgb(0, 0, 0)'));

        $this->assertEquals([
            'red' => 30,
            'green' => 100,
            'blue' => 40
        ], ColorUtils::parseColorToRgb('rgb(30, 100, 40)'));

        $this->assertEquals([
            'red' => 30,
            'green' => 100,
            'blue' => 40
        ], ColorUtils::parseColorToRgb('rgba(30, 100, 40, 0.5)'));

        $this->assertEquals([
            'red' => 30,
            'green' => 100,
            'blue' => 40
        ], ColorUtils::parseColorToRgb('#1e6428'));

        $this->assertEquals([
            'red' => 30,
            'green' => 100,
            'blue' => 40
        ], ColorUtils::parseColorToRgb('#1e642855'));

        // HSL conversion is not exact
        $this->assertEquals([
            'red' => 31,
            'green' => 101,
            'blue' => 41
        ], ColorUtils::parseColorToRgb('hsl(129, 53%, 26%)'));

        $this->assertEquals([
            'red' => 31,
            'green' => 101,
            'blue' => 41
        ], ColorUtils::parseColorToRgb('hsla(129, 53%, 26%, 0.5)'));
    }


}
