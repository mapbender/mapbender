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
        $upstream = parent::getRegionTemplateVars($application, $regionName);
        switch ($regionName) {
            default:
                return $upstream;
            case 'sidepane':
                return array_replace($upstream, array(
                    'region_class' => 'right',
                ));
        }
    }
}
