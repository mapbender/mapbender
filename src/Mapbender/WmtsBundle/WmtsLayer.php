<?php

namespace Mapbender\WmtsBundle;

use Mapbender\CoreBundle\Component\LayerInterface;

/**
 * Base WMTS class
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */
class WmtsLayer implements LayerInterface {
    protected $id;
    protected $configuration;

    public function __construct($id, array $configuration) {
        $this->id = $id;
        $this->configuration = $configuration;
    }

    public function render() {
        return array(
            'id' => $this->id,
            'type' => 'wmts',
            'configuration' => $this->configuration,
        );
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.layer.wmts.js'
            )
        );
    }
}

