<?php

namespace Mapbender\CoreBundle\Tests;

use Mapbender\CoreBundle\Element\FeatureInfo;
use PHPUnit\Framework\TestCase;

class TestColorConversion extends TestCase
{
    public function testColorConversion() {
        $this->assertEquals('rgba(0, 0, 0, 0)', FeatureInfo::addOpacityToColor([
            'color' => 'rgb(0, 0, 0)',
            'opacity' => 0,
        ], 'color', 'opacity'));

        $this->assertEquals('rgba(0, 0, 0, 1)', FeatureInfo::addOpacityToColor([
            'color' => 'rgb(0, 0, 0)',
            'opacity' => 100,
        ], 'color', 'opacity'));

        $this->assertEquals('rgba(0, 0, 0, 0.5)', FeatureInfo::addOpacityToColor([
            'color' => 'rgb(0, 0, 0)',
            'opacity' => 50,
        ], 'color', 'opacity'));

        // rgba should stay the same
        $this->assertEquals('rgba(0, 0, 0, 0.5)', FeatureInfo::addOpacityToColor([
            'color' => 'rgba(0, 0, 0, 0.5)',
            'opacity' => 66,
        ], 'color', 'opacity'));

        // 6 digit hex codes should be converted to rgba
        $this->assertEquals('rgba(0, 0, 0, 0)', FeatureInfo::addOpacityToColor([
            'color' => '#000000',
            'opacity' => 0,
        ], 'color', 'opacity'));

        $this->assertEquals('rgba(0, 0, 0, 1)', FeatureInfo::addOpacityToColor([
            'color' => '#000000',
            'opacity' => 100,
        ], 'color', 'opacity'));

        $this->assertEquals('rgba(0, 0, 0, 0.5)', FeatureInfo::addOpacityToColor([
            'color' => '#000000',
            'opacity' => 50,
        ], 'color', 'opacity'));

        // 8 digit hex codes can stay as they are
        $this->assertEquals('#0000007f', FeatureInfo::addOpacityToColor([
            'color' => '#0000007f',
            'opacity' => 66,
        ], 'color', 'opacity'));
    }
}
