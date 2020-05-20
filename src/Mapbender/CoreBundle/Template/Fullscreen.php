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

    public function getRegionTemplate(\Mapbender\CoreBundle\Entity\Application $application, $regionName)
    {
        switch ($regionName) {
            default:
                return parent::getRegionTemplate($application, $regionName);
            case 'toolbar':
                return 'MapbenderCoreBundle:Template:fullscreen/toolbar.html.twig';
        }
    }

    public function getRegionTemplateVars(\Mapbender\CoreBundle\Entity\Application $application, $regionName)
    {
        $upstream = parent::getRegionTemplateVars($application, $regionName);
        switch ($regionName) {
            default:
                return $upstream;
            case 'sidepane':
                $alignment = 'left';
                $props = $this->extractRegionProperties($application, $regionName);
                if (!empty($props['closed'])) {
                    $alignment .= ' closed';
                }
                return array_replace($upstream, array(
                    'alignment_class' => $alignment,
                ));
        }
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
                    '/components/vis-ui.js/vis-ui.js-built.js',
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
