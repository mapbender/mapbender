<?php

namespace Mapbender\VectorTilesBundle;

use Mapbender\CoreBundle\Component\Source\DataSource;
use Mapbender\VectorTilesBundle\Component\VectorTilesConfigGenerator;
use Mapbender\VectorTilesBundle\Component\VectorTilesInstanceFactory;
use Mapbender\VectorTilesBundle\Component\VectorTilesLoader;
use Mapbender\VectorTilesBundle\Component\VectorTilesRenderer;

class VectorTilesDataSource extends DataSource
{

    public function __construct(
        private VectorTilesConfigGenerator   $configService,
        private VectorTilesInstanceFactory $instanceFactory,
        private VectorTilesLoader                $loader,
        private VectorTilesRenderer     $layerRenderer,
    )
    {
    }

    public function getName(): string
    {
        return "vector_tiles";
    }

    public function getLabel(): string
    {
        return "Vector Tiles";
    }

    public function getConfigGenerator(): VectorTilesConfigGenerator
    {
        return $this->configService;
    }

    public function getInstanceFactory(): VectorTilesInstanceFactory
    {
        return $this->instanceFactory;
    }

    public function getLoader(): VectorTilesLoader
    {
        return $this->loader;
    }

    public function getLayerRenderer(): VectorTilesRenderer
    {
        return $this->layerRenderer;
    }

    public function getMetadataBackendTemplate(): ?string
    {
        // TODO: change this
        return '@MapbenderWmts/Repository/view.html.twig';
    }
}
