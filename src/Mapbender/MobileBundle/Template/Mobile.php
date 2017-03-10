<?php

namespace Mapbender\MobileBundle\Template;

use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Component\Template;

/**
 * Template Mobile Template
 *
 */
class Mobile extends Template
{

    /**
     * @inheritdoc
     */
    public static function getTitle()
    {
        return 'Mapbender Mobile template';
    }

    /**
     * @inheritdoc
     */
    public static function listAssets()
    {
        $assets = array(
            'css' => array(
//                '@MapbenderMobileBundle/Resources/public/sass/theme/mobile.scss',
//                '@MapbenderCoreBundle/Resources/public/sass/theme/mapbender3.scss',
            ),
            'js' => array(
                '/components/underscore/underscore-min.js',
                '@MapbenderMobileBundle/Resources/public/js/mapbender.mobile.js',
                '@MapbenderMobileBundle/Resources/public/js/vendors/jquery.mobile.custom.min.js',
                '@MapbenderMobileBundle/Resources/public/js/mobile.js',
                '@MapbenderCoreBundle/Resources/public/regional/vendor/notify.0.3.2.min.js',
            ),
            'trans' => array(),
        );

        return $assets;
    }

    /**
     * @inheritdoc
     */
    public function getLateAssets($type)
    {
        $assets = array(
            'css' => array(
                '@MapbenderMobileBundle/Resources/public/sass/theme/mobile.scss'
            ),
            'js' => array(),
            'trans' => array()
        );
        return $assets[$type];
    }

    /**
     * @inheritdoc
     */
    public static function getRegions()
    {
        return array('footer', 'content', 'mobilePane');
    }

    /**
     * @inheritdoc
     */
    public function render($format = 'html', $html = true, $css = true, $js = true)
    {
        $uploads_dir = Application::getAppWebDir($this->container, $this->application->getSlug());
        $templating = $this->container->get('templating');
        return $templating->render(
            'MapbenderMobileBundle:Template:mobile.html.twig',
            array(
                'html' => $html,
                'css' => $css,
                'js' => $js,
                'application' => $this->application,
                'uploads_dir' => $uploads_dir
            )
        );
    }
}
