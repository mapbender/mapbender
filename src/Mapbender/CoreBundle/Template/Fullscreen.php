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
    public static function getRegionsProperties()
    {
        return array(
            'sidepane' => array(
                'tabs' => array(
                    'name' => 'tabs',
                    'label' => 'mb.manager.template.region.tabs.label',
                ),
                'accordion' => array(
                    'name' => 'accordion',
                    'label' => 'mb.manager.template.region.accordion.label',
                ),
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getTitle()
    {
        return 'Fullscreen';
    }

    /**
     * @inheritdoc
     */
    public function getAssets($type)
    {
        switch ($type) {
            case 'css':
                return array(
                    '@MapbenderCoreBundle/Resources/public/sass/template/fullscreen.scss',
                );
            case 'js':
                return array(
                    '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
                    '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.container.info.js',
                    '/components/jquerydialogextendjs/jquerydialogextendjs-built.js',
                    '/../vendor/mapbender/vis-ui.js/src/js/elements/confirm.dialog.js',
                    '/../vendor/mapbender/vis-ui.js/src/js/elements/data.result-table.js',
                    '/../vendor/mapbender/vis-ui.js/src/js/elements/date.selector.js',
                    '/../vendor/mapbender/vis-ui.js/src/js/elements/popup.dialog.js',
                    '/../vendor/mapbender/vis-ui.js/src/js/elements/tab.navigator.js',
                    '/../vendor/mapbender/vis-ui.js/src/js/utils/DataUtil.js',
                    '/../vendor/mapbender/vis-ui.js/src/js/utils/fn.formData.js',
                    '/../vendor/mapbender/vis-ui.js/src/js/utils/StringHelper.js',
                    '/../vendor/mapbender/vis-ui.js/src/js/jquery.form.generator.js',
                );
            case 'trans':
            default:
                return parent::getAssets($type);
        }
    }

    /**
     * @inheritdoc
     */
    public static function getRegions()
    {
        return array('toolbar', 'sidepane', 'content', 'footer');
    }

    public function getTwigTemplate()
    {
        return 'MapbenderCoreBundle:Template:fullscreen.html.twig';
    }
}
