<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\Component\Enumeration\ScreenTypes;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Utils\ArrayUtil;

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

    public function getRegionClasses(\Mapbender\CoreBundle\Entity\Application $application, $regionName)
    {
        $classes = parent::getRegionClasses($application, $regionName);
        $props = $this->extractRegionProperties($application, $regionName);
        switch (ArrayUtil::getDefault($props, 'screenType')) {
            default:
            case ScreenTypes::ALL;
                // nothing;
                break;
            case ScreenTypes::DESKTOP_ONLY:
                $classes[] = 'hide-screentype-mobile';
                break;
            case ScreenTypes::MOBILE_ONLY:
                $classes[] = 'hide-screentype-desktop';
                break;
        }
        switch ($regionName) {
            default:
                break;
            case 'sidepane':
                $classes[] = 'left';
                if (!empty($props['closed'])) {
                    $classes[] = 'closed';
                }
                break;
        }
        return $classes;
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

    public function getBodyClass(\Mapbender\CoreBundle\Entity\Application $application)
    {
        return 'desktop-template';
    }

    /**
     * @param string $regionName
     * @return string|null
     */
    public static function getRegionSettingsFormType($regionName)
    {
        switch ($regionName) {
            case 'sidepane':
                return 'Mapbender\CoreBundle\Form\Type\Template\Fullscreen\SidepaneSettingsType';
            case 'toolbar':
            case 'footer':
            return 'Mapbender\CoreBundle\Form\Type\Template\Fullscreen\ToolbarSettingsType';
            default:
                return null;
        }
    }
}
