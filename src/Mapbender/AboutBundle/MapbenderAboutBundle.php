<?php
namespace Mapbender\AboutBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

/**
 * @author Christian Wigoda
 * @author Paul Schmidt
 * @author Vadim Hermann
 */
class MapbenderAboutBundle extends MapbenderBundle
{
    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
            'Mapbender\AboutBundle\Element\AboutDialog'
        );
    }
}

