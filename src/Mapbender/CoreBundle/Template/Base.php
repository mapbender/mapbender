<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\Template;

class Base extends Template {
    public static function getTitle() {
        return 'Mapbender\'s simple template';
    }

    public function getAssets($type) {
        parent::getAssets($type);
        $assets = array(
            'css' => array('@MapbenderCoreBundle/Resources/public/mapbender.template.base.css'),
            'js' => array(),
        );

        return $assets[$type];
    }

    public static function getRegions() {
        return array('top', 'content', 'footer');
    }

    public function render($format = 'html', $html = true, $css = true,
        $js = true) {
        $templating = $this->container->get('templating');
        return $templating
            ->render('MapbenderCoreBundle:Template:base.html.twig',
                array(
                    'html' => $html,
                    'css' => $css,
                    'js' => $js,
                    'application' => $this->application));
    }

}

