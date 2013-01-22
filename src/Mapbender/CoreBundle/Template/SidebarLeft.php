<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\Template;

class SidebarLeft extends Template {
    public static function getTitle() {
        return 'Sidebar Left: Mapbender\'s simple template';
    }

    public function getAssets($type) {
        parent::getAssets($type);
        $assets = array(
            'css' => array('@MapbenderCoreBundle/Resources/public/mapbender.template.sidebarleft.css'),
            'js' => array(),
        );

        return $assets[$type];
    }

    public static function getRegions() {
        return array('top', 'sidebarleft', 'content', 'footer');
    }

    public function render($format = 'html', $html = true, $css = true,
        $js = true) {
        $templating = $this->container->get('templating');
        return $templating
            ->render('MapbenderCoreBundle:Template:sidebarleft.html.twig',
                array(
                    'html' => $html,
                    'css' => $css,
                    'js' => $js,
                    'application' => $this->application));
    }

}