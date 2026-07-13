<?php

namespace Mapbender\VectorTilesBundle;

use Mapbender\CoreBundle\Component\Source\DataSource;
use Mapbender\VectorTilesBundle\Component\VectorTilesConfigGenerator;
use Mapbender\VectorTilesBundle\Component\VectorTilesInstanceFactory;
use Mapbender\VectorTilesBundle\Component\VectorTilesLoader;
use Mapbender\VectorTilesBundle\Component\VectorTilesRenderer;
use Mapbender\VectorTilesBundle\Entity\VectorTileSource;

class VectorTilesDataSource extends DataSource
{
    const TYPE = "vector_tiles";

    public function __construct(
        private readonly VectorTilesConfigGenerator   $configService,
        private readonly VectorTilesInstanceFactory $instanceFactory,
        private readonly VectorTilesLoader                $loader,
        private readonly VectorTilesRenderer     $layerRenderer,
    )
    {
    }

    public function getName(): string
    {
        return self::TYPE;
    }

    public function getLabel(bool $compact = false): string
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

    public function getSourceEntityClass(): string
    {
        return VectorTileSource::class;
    }

    public function getAccentColor(): string
    {
        return 'warning';
    }

    public function getMetadataBackendTemplate(): ?string
    {
        return '@MapbenderVectorTiles/view.html.twig';
    }

    public function getEntityTypeDiscriminator(): string
    {
        return self::TYPE;
    }
}
