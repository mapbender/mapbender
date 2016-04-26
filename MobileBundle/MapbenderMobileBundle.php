<?php

namespace Mapbender\MobileBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

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
