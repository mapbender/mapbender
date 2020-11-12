<?php

namespace Mapbender\CoreBundle\Template;


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

    public function getRegionTemplateVars(\Mapbender\CoreBundle\Entity\Application $application, $regionName)
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

    public function getRegionClasses(\Mapbender\CoreBundle\Entity\Application $application, $regionName)
    {
        $classes = parent::getRegionClasses($application, $regionName);
        if ($regionName === 'sidepane') {
            $removeIndex = array_search('left', $classes, true);
            if ($removeIndex !== false) {
                unset($classes[$removeIndex]);
            }
            $classes[] = 'right';
        }
        return $classes;
    }
}
