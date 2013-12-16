<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\Template;

/**
 * Template Classic
 */
class Classic extends Template
{

    /**
     * @inheritdoc
     */
    public static function getTitle()
    {
        return 'Classic template';
    }

    /**
     * @inheritdoc
     */
    public function getAssets($type)
    {
        parent::getAssets($type);
        $assets = array(
            'css' => array('@FOMCoreBundle/Resources/public/css/frontend/classic.css'),
            'js' => array('@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                          '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js'),
            'trans' => array()
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
                        ->render('MapbenderCoreBundle:Template:classic.html.twig',
                                 array(
                            'html' => $html,
                            'css' => $css,
                            'js' => $js,
                            'application' => $this->application));
    }

}