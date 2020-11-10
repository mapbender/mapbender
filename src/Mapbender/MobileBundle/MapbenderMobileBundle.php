<?php

namespace Mapbender\MobileBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

/**
 * Class MapbenderMobileBundle
 *
 * @package Mapbender\MobileBundle
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author Andriy Oblivantsev <eslider@gmail.com>
 */
class MapbenderMobileBundle extends MapbenderBundle
{

    /**
     * @inheritdoc
     */
    public function getTemplates()
    {
        return array('Mapbender\MobileBundle\Template\Mobile');
    }

    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(

        );
    }
}
