<?php

namespace Mapbender\WmsBundle;

use Mapbender\CoreBundle\Component\Source\DataSource;
use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Component\Source\SourceLoader;
use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\PrintBundle\Component\LayerRenderer;
use Mapbender\WmsBundle\Component\LayerRendererWms;
use Mapbender\WmsBundle\Component\Presenter\WmsSourceInstanceConfigGenerator;
use Mapbender\WmsBundle\Component\Wms\Importer;

class WmsDataSource extends DataSource
{

    public function __construct(
        private WmsSourceInstanceConfigGenerator $configGenerator,
        private SourceInstanceFactory            $instanceFactory,
        private Importer                         $loader,
        private LayerRendererWms                 $layerRenderer,
    )
    {
    }

    public function getName(): string
    {
        return "wms";
    }

    public function getConfigGenerator(): SourceInstanceConfigGenerator
    {
        return $this->configGenerator;
    }

    public function getInstanceFactory(): SourceInstanceFactory
    {
        return $this->instanceFactory;
    }

    public function getLoader(): SourceLoader
    {
        return $this->loader;
    }

    public function getLayerRenderer(): LayerRenderer
    {
        return $this->layerRenderer;
    }
}
