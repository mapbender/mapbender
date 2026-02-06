<?php

namespace Mapbender\WmsBundle;

use Mapbender\CoreBundle\Component\Source\DataSource;
use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Component\Source\SourceLoader;
use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\PrintBundle\Component\LayerRenderer;
use Mapbender\WmsBundle\Component\LayerRendererWms;
use Mapbender\WmsBundle\Component\Presenter\WmsSourceInstanceConfigGenerator;
use Mapbender\WmsBundle\Component\Wms\Importer;
use Mapbender\WmsBundle\Entity\WmsSource;

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

    public const TYPE = "WMS";

    public function getName(): string
    {
        return self::TYPE;
    }

    public function getLabel(bool $compact = false): string
    {
        return $compact ? "WMS" : "OGC WMS";
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

    public function getSourceEntityClass(): string
    {
        return WmsSource::class;
    }

    public function getMetadataFrontendTemplate(): ?string
    {
        return '@MapbenderWms/frontend/instance.html.twig';
    }

    public function getMetadataBackendTemplate(): ?string
    {
        return '@MapbenderWms/Repository/view.html.twig';
    }

    public function areMetadataUrlsInternal(SourceInstance $instance): bool
    {
        return $this->configGenerator->useTunnel($instance);
    }
}
