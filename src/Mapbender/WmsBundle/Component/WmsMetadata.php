<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceMetadata;
use Mapbender\CoreBundle\Controller\ApplicationController;

/**
 * Collects template variables from a WmsInstance for MapbenderCoreBundle::metadata.html.twig
 * Renders frontend meta data for an entire Wms source or an individual layer.
 * @deprecated this entire thing should be implemented purely in twig
 * @see ApplicationController::metadataAction()
 *
 * @inheritdoc
 * @author Paul Schmidt
 */
class WmsMetadata extends SourceMetadata
{

    public function getTemplate()
    {
        return 'MapbenderCoreBundle::metadata.html.twig';
    }

}
