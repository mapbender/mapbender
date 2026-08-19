<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Form\Type\Template\Fullscreen\SidepaneSettingsType;
use Mapbender\CoreBundle\Form\Type\Template\Fullscreen\ToolbarSettingsType;
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
    public static function getRegionsProperties(): array
    {
        return [
            'sidepane' => [
                'accordion' => [
                    'name' => 'accordion',
                ],
                'tabs' => [
                    'name' => 'tabs',
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getTitle(): string
    {
        return 'Fullscreen';
    }

    public function getRegionTemplate(Application $application, $regionName)
    {
        return match ($regionName) {
            'toolbar' => '@MapbenderCore/Template/fullscreen/toolbar.html.twig',
            default => parent::getRegionTemplate($application, $regionName),
        };
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

    public function getSassVariablesAssets(Application $application): array
    {
        return [
            '@MapbenderCoreBundle/Resources/public/sass/libs/_variables.scss',
            '@MapbenderCoreBundle/Resources/public/sass/template/fullscreen_variables.scss',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAssets($type)
    {
        return match ($type) {
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/template/fullscreen.scss',
                '@MapbenderCoreBundle/Resources/public/sass/modules/_popup_dialog.scss',
                '@MapbenderCoreBundle/Resources/public/sass/modules/_tab_navigator.scss',
            ],
            'js' => [
                '@MapbenderCoreBundle/Resources/public/sidepane/sidepane.js',
                '@MapbenderCoreBundle/Resources/public/sidepane/sidepane-accordion.js',
                '@MapbenderCoreBundle/Resources/public/sidepane/sidepane-list.js',
                '@MapbenderCoreBundle/Resources/public/sidepane/sidepane-tabs.js',
                '@MapbenderCoreBundle/Resources/public/sidepane/sidepane-unformatted.js',
                '@MapbenderCoreBundle/Resources/public/sidepane/sidepane-init.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.container.info.js',
            ],
            default => parent::getAssets($type),
        };
    }

    /**
     * @inheritdoc
     */
    public static function getRegions(): array
    {
        return ['toolbar', 'sidepane', 'content', 'footer'];
    }

    public function getTwigTemplate(): string
    {
        return '@MapbenderCore/Template/fullscreen.html.twig';
    }

    public function getBodyClass(Application $application): string
    {
        return 'desktop-template';
    }

    /**
     * @param string $regionName
     * @return string|null
     */
    public static function getRegionSettingsFormType($regionName): ?string
    {
        return match ($regionName) {
            'sidepane' => SidepaneSettingsType::class,
            'toolbar', 'footer' => ToolbarSettingsType::class,
            default => null,
        };
    }

    public static function getRegionPropertiesDefaults($regionName)
    {
        return match ($regionName) {
            'toolbar', 'footer' => [
                'item_alignment' => 'right',
                'generate_button_menu' => false,
            ],
            'sidepane' => [
                'name' => 'accordion',
                'align' => 'left',
                'closed' => false,
                'resizable' => true,
            ],
            default => parent::getRegionPropertiesDefaults($regionName),
        };
    }
}
