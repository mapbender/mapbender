<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\WmtsBundle\Entity\Theme;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 *
 * @property Theme $entity
 */
class ThemeEntityHandler extends EntityHandler
{

    /**
     * @inheritdoc
     */
    public function create()
    {
        
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        parent::save();
        foreach ($this->entity->getThemes() as $theme) {
            $recursionHandler = new ThemeEntityHandler($this->container, $theme);
            $recursionHandler->save();
        }
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        foreach ($this->entity->getThemes() as $theme) {
            $recursionHandler = new ThemeEntityHandler($this->container, $theme);
            $recursionHandler->remove();
        }
        parent::remove();
    }
}
