<?php


namespace Mapbender\PrintBundle\Component\Region;


use Mapbender\PrintBundle\Component\TemplateRegion;

/**
 * Empty space where nothing fits.
 * Used to trick LegendHandler into an immediate page break when continuing legends past the main page.
 */
class NullRegion extends TemplateRegion
{
    private static $instance;

    public function __construct()
    {
        parent::__construct(0, 0);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
