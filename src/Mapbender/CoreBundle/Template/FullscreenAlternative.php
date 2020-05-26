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
            case 'sidepane':
                $vars['alignment_class'] = str_replace('left', 'right', $vars['alignment_class']);
                return $vars;
            case 'toolbar':
                return array_replace($vars, array(
                    'alignment_class' => 'itemsLeft',
                ));
        }
    }
}
