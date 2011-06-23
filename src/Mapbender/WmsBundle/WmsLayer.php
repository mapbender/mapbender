<?php

namespace Mapbender\WmsBundle;

use Mapbender\CoreBundle\Component\LayerInterface;

/**
 * Base WMS class
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */
class WmsLayer implements LayerInterface {
	protected $title;
	protected $configuration;

	public function __construct($title, array $configuration) {
		$this->title = $title;
		$this->configuration = $configuration;
	}

	public function render() {
		return array(
            'title' => $this->title,
            'type' => 'wms',
			'configuration' => $this->configuration,
		);
	}
}

