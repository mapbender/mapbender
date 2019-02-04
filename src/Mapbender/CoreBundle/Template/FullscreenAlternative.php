<?php

namespace Mapbender\CoreBundle\Template;

/**
 * Template FullscreenAlternative
 *
 * @author Christian Wygoda
 * @author Andriy Oblivantsev
 *
 * @deprecated
 */
class FullscreenAlternative extends Fullscreen
{
    protected static $title = "Fullscreen alternative";
    protected static $css   = array(
        '@MapbenderCoreBundle/Resources/public/sass/template/fullscreen_alternative.scss'
    );
    protected static $js    = array(
        '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
        '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js'
    );

    public $twigTemplate = 'MapbenderCoreBundle:Template:fullscreen_alternative.html.twig';
}
