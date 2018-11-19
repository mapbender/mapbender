<?php

namespace Mapbender\CoreBundle\Template;


/**
 * Template FullscreenAlternative
 *
 * @author Christian Wygoda
 */
class FullscreenAlternative extends Fullscreen
{

    /**
     * @inheritdoc
     */
    public static function getTitle()
    {
        return 'Fullscreen alternative';
    }

    /**
     * {@inheritdoc}
     */
    public function getAssets($type)
    {
        switch ($type) {
            case 'css':
                return array(
                    '@MapbenderCoreBundle/Resources/public/sass/template/fullscreen_alternative.scss',
                );
            case 'js':
            case 'trans':
            default:
                return parent::getAssets($type);
        }
    }

    public function getTwigTemplate()
    {
        return 'MapbenderCoreBundle:Template:fullscreen_alternative.html.twig';
    }

}
