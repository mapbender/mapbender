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

    public $twigTemplate = 'MapbenderCoreBundle:Template:classic.html.twig';

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array(
            'css'   => array(
                '@MapbenderCoreBundle/Resources/public/sass/template/classic.scss'),
            'js'    => array(
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js'),
            'trans' => array()
        );
    }
}
