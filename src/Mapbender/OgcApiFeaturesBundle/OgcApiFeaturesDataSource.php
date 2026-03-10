<?php

namespace Mapbender\OgcApiFeaturesBundle;

use Mapbender\CoreBundle\Component\Source\DataSource;
use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Component\Source\SourceLoader;
use Mapbender\PrintBundle\Component\LayerRenderer;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesSource;
use Mapbender\OgcApiFeaturesBundle\Component\OgcApiFeaturesConfigGenerator;
use Mapbender\OgcApiFeaturesBundle\Component\OgcApiFeaturesInstanceFactory;
use Mapbender\OgcApiFeaturesBundle\Component\OgcApiFeaturesLoader;
use Mapbender\OgcApiFeaturesBundle\Component\OgcApiFeaturesRenderer;

class OgcApiFeaturesDataSource extends DataSource
{
    const TYPE = 'ogc_api_features';

    public function __construct(
        private readonly OgcApiFeaturesConfigGenerator $configService,
        private readonly OgcApiFeaturesInstanceFactory $instanceFactory,
        private readonly OgcApiFeaturesLoader $loader,
        private readonly OgcApiFeaturesRenderer $layerRenderer
    )
    {
    }

    public function getName(): string
    {
        return self::TYPE;
    }

    public function getLabel(bool $compact = false): string
    {
        return $compact ? 'OGC-Features' : 'OGC API Features';
    }

    public function getConfigGenerator(): SourceInstanceConfigGenerator
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

    public function getSourceEntityClass(): string
    {
        return OgcApiFeaturesSource::class;
    }

    public function getMetadataFrontendTemplate(): ?string
    {
        return '@MapbenderOgcApiFeatures/metadata-frontend.html.twig';
    }

    public function getMetadataBackendTemplate(): ?string
    {
        return '@MapbenderOgcApiFeatures/metadata-backend.html.twig';
    }

    public function getEntityTypeDiscriminator(): string
    {
        return self::TYPE;
    }
}
