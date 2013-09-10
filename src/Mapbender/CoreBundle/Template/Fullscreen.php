<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\Template;

/**
 * Template Fullscreen
 * 
 * @author Christian Wygoda
 */
class Fullscreen extends Template
{

    /**
     * @inheritdoc
     */
    public static function getTitle()
    {
        return 'Fullscreen template';
    }

    /**
     * @inheritdoc
     */
    public function getAssets($type)
    {
        parent::getAssets($type);
        $assets = array(
            'css' => array('@FOMCoreBundle/Resources/public/css/frontend/fullscreen.css'),
            'js' => array('@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                          '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js'),
        );

        return $assets[$type];
    }

    /**
     * @inheritdoc
     */
    public static function getRegions()
    {
        return array('toolbar', 'sidepane', 'content', 'footer');
    }

    /**
     * @inheritdoc
     */
    public function render($format = 'html', $html = true, $css = true,
            $js = true)
    {
        $templating = $this->container->get('templating');
        return $templating
                        ->render('MapbenderCoreBundle:Template:fullscreen.html.twig',
                                 array(
                            'html' => $html,
                            'css' => $css,
                            'js' => $js,
                            'application' => $this->application));
    }

}