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

    public function getRegionTemplateVars(Application $application, $regionName)
    {
        $vars = parent::getRegionTemplateVars($application, $regionName);
        switch ($regionName) {
            default:
                return $vars;
            case 'toolbar':
                return array_replace($vars, array(
                    'alignment_class' => 'itemsLeft',
                ));
        }
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
}
