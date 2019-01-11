<?php

namespace Mapbender\CoreBundle\Template;

/**
 * Template Classic
 *
 * @deprecated
 */
class Classic extends Fullscreen
{
    protected static $title   = "Classic template";
    protected static $regions = array('toolbar', 'sidepane', 'content', 'footer');
    protected static $css     = array(
        '@MapbenderCoreBundle/Resources/public/sass/template/classic.scss',
    );
    protected static $js      = array(
        '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js'
    );

    public $twigTemplate = 'MapbenderCoreBundle:Template:classic.html.twig';
}
