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
    public static function getTitle()
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
                $classes = array(ArrayUtil::getDefault($props, 'align') ?: 'right');
                if (!empty($props['closed'])) {
                    $classes[] = 'closed';
                }
                return $classes;
        }
    }

    public static function getRegionPropertiesDefaults($regionName)
    {
        switch ($regionName) {
            case 'toolbar':
            case 'footer':
                return \array_replace(parent::getRegionPropertiesDefaults($regionName), array(
                    'item_alignment' => 'left',
                ));
            case 'sidepane':
                return \array_replace(parent::getRegionPropertiesDefaults($regionName), array(
                    'align' => 'right',
                ));
            default:
                return parent::getRegionPropertiesDefaults($regionName);
        }
    }
}
