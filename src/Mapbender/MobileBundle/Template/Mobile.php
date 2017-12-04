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
    /** @var string Application title */
    protected static $title = 'Mapbender Mobile template';

    /** @var string Application TWIG template path */
    protected $twigTemplate = 'MapbenderMobileBundle:Template:mobile.html.twig';

    /** @var array Late assets */
    protected $lateAssets = array(
        'js'    => array(),
        'css'   => array(
            '@MapbenderMobileBundle/Resources/public/sass/theme/mobile.scss'
        ),
        'trans' => array(),
    );

    protected static $js  = array(
        '/components/underscore/underscore-min.js',
        '@MapbenderMobileBundle/Resources/public/js/mapbender.mobile.js',
        '@MapbenderMobileBundle/Resources/public/js/vendors/jquery.mobile.custom.min.js',
        '@MapbenderMobileBundle/Resources/public/js/mobile.js',
        '@MapbenderCoreBundle/Resources/public/regional/vendor/notify.0.3.2.min.js'
    );

    /**  @var array Region names */
    protected static $regions = array('footer', 'content', 'mobilePane');

    /**
     * @inheritdoc
     */
    public function render($format = 'html', $html = true, $css = true, $js = true)
    {
        $templateEngine = $this->container->get('templating');
        return $templateEngine->render($this->twigTemplate, array(
                'html'        => $html,
                'css'         => $css,
                'js'          => $js,
                'application' => $this->application,
                'uploads_dir' => Application::getAppWebDir($this->container, $this->application->getSlug())
            )
        );
    }
}
