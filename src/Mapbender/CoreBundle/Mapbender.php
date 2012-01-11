<?php

/**
 * Mapbender - The central Mapbender3 service. Provides metadata about
 * available elements, layers and templates.
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */

namespace Mapbender\CoreBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Mapbender {
    private $elements = array();
    private $layers = array();
    private $templates = array();

    /**
     * Iterate over all bundles and if is an MapbenderBundle, get list
     * of elements, layers and templates
     */
    public function __construct(ContainerInterface $container) {
        $bundles = $container->get('kernel')->getBundles();
        foreach($bundles as $bundle) {
            if(is_subclass_of($bundle,
                'Mapbender\CoreBundle\Component\MapbenderBundle')) {

                $this->elements = array_merge($this->elements,
                    $bundle->getElements());
                $this->layer =  array_merge($this->layers,
                    $bundle->getLayers());
                $this->templates = array_merge($this->templates,
                    $bundle->getTemplates());
            }
        }
    }

    public function getElements() {
        return $this->elements;
    }

    public function getLayers() {
        return $this->layers;
    }

    public function getTemplates() {
        return $this->templates;
    }
}

