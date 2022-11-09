<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;
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
                'accordion' => array(
                    'name' => 'accordion',
                ),
                'tabs' => array(
                    'name' => 'tabs',
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

    public function getRegionTemplate(Application $application, $regionName)
    {
        switch ($regionName) {
            default:
                return parent::getRegionTemplate($application, $regionName);
            case 'toolbar':
                return 'MapbenderCoreBundle:Template:fullscreen/toolbar.html.twig';
        }
    }

    public function getRegionClasses(Application $application, $regionName)
    {
        $classes = parent::getRegionClasses($application, $regionName);
        switch ($regionName) {
            default:
                break;
            case 'sidepane':
                $props = $this->extractRegionProperties($application, $regionName);
                $classes[] = ArrayUtil::getDefault($props, 'align') ?: 'left';
                if (!empty($props['closed'])) {
                    $classes[] = 'closed';
                }
                break;
        }
        return $classes;
    }

    public function getSassVariablesAssets(Application $application)
    {
        return array(
            '@MapbenderCoreBundle/Resources/public/sass/libs/_variables.scss',
            '@MapbenderCoreBundle/Resources/public/sass/template/fullscreen_variables.scss',
        );
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
                    '@MapbenderCoreBundle/Resources/public/sass/modules/_popup_dialog.scss',
                    '@MapbenderCoreBundle/Resources/public/sass/modules/_tab_navigator.scss',
                    '@MapbenderCoreBundle/Resources/public/sass/modules/_result_table.scss',
                );
            case 'js':
                return array(
                    '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
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

    public function getBodyClass(Application $application)
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

    public static function getRegionPropertiesDefaults($regionName)
    {
        switch ($regionName) {
            case 'toolbar':
            case 'footer':
                return array(
                    'item_alignment' => 'right',
                    'generate_button_menu' => false,
                );
            case 'sidepane':
                return array(
                    'name' => 'accordion',
                    'align' => 'left',
                    'closed' => false,
                );
            default:
                return parent::getRegionPropertiesDefaults($regionName);
        }
    }
}
