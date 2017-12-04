<?php
namespace Mapbender\PrintBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

/**
 * PrintBundle.
 *
 * @author Stefan Winkelmann
 */
class MapbenderPrintBundle extends MapbenderBundle
{
    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
            'Mapbender\PrintBundle\Element\ImageExport'
            );
    }

}

