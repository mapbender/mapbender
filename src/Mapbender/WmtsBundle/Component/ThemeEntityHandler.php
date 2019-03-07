<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\EntityHandler;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
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
        $this->container->get('doctrine')->getManager()->persist($this->entity);
        foreach ($this->entity->getThemes() as $theme) {
            self::createHandler($this->container, $theme)->save();
        }
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        foreach ($this->entity->getThemes() as $theme) {
            self::createHandler($this->container, $theme)->remove();
        }
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }
}
