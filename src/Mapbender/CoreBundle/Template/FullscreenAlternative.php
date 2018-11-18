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
    protected static $title        = "Fullscreen alternative";

    public           $twigTemplate = 'MapbenderCoreBundle:Template:fullscreen_alternative.html.twig';

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        $assets = array(
            'css'   => array('@MapbenderCoreBundle/Resources/public/sass/template/fullscreen_alternative.scss'),
            'js'    => array('@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                             '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
                             '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js'),
            'trans' => array()
        );
        return $assets;
    }
}
