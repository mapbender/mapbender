<?php


namespace Mapbender\Component\Enumeration;


class ScreenTypes
{
    const ALL = 'all';
    const MOBILE_ONLY = 'mobile';
    const DESKTOP_ONLY = 'desktop';

    public static function getValidValues()
    {
        return array(
            static::ALL,
            static::MOBILE_ONLY,
            static::DESKTOP_ONLY,
        );
    }
}
