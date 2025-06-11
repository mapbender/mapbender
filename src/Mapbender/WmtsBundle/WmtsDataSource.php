<?php

namespace Mapbender\WmtsBundle;

use Mapbender\CoreBundle\Component\Source\DataSource;
use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Component\Source\SourceLoader;
use Mapbender\CoreBundle\Component\Source\SourceService;
use Mapbender\PrintBundle\Component\LayerRenderer;
use Mapbender\WmtsBundle\Component\Export\LayerRendererWmts;
use Mapbender\WmtsBundle\Component\Presenter\ConfigGeneratorWmts;
use Mapbender\WmtsBundle\Component\Wmts\Loader;

class WmtsDataSource extends DataSource
{

    public function __construct(
        private ConfigGeneratorWmts   $configService,
        private SourceInstanceFactory $instanceFactory,
        private Loader                $loader,
        private LayerRendererWmts     $layerRenderer,
    )
    {
    }

    public function getName(): string
    {
        return "wmts";
    }

    public function getConfigService(): SourceService
    {
        return $this->configService;
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
