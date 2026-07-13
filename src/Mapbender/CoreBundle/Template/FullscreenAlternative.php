<?php

namespace Mapbender\CoreBundle\Template;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Utils\ArrayUtil;

/**
 * Template FullscreenAlternative
 *
 * @author Christian Wygoda
 */
class FullscreenAlternative extends Fullscreen
{
    /**
     * @inheritdoc
     */
    public static function getTitle(): string
    {
        return 'Fullscreen alternative';
    }

    public function getRegionClasses(Application $application, $regionName)
    {
        switch ($regionName) {
            default:
                return parent::getRegionClasses($application, $regionName);
            case 'sidepane':
                $props = $this->extractRegionProperties($application, $regionName);
                $classes = [ArrayUtil::getDefault($props, 'align') ?: 'right'];
                if (!empty($props['closed'])) {
                    $classes[] = 'closed';
                }
                return $classes;
        }
    }

    public static function getRegionPropertiesDefaults($regionName)
    {
        return match ($regionName) {
            'toolbar', 'footer' => \array_replace(parent::getRegionPropertiesDefaults($regionName), [
                'item_alignment' => 'left',
            ]),
            'sidepane' => \array_replace(parent::getRegionPropertiesDefaults($regionName), [
                'align' => 'right',
            ]),
            default => parent::getRegionPropertiesDefaults($regionName),
        };
    }
}
