<?php

namespace Mapbender\XyzBundle;

use Mapbender\CoreBundle\Component\Source\DataSource;
use Mapbender\XyzBundle\Component\XyzConfigGenerator;
use Mapbender\XyzBundle\Component\XyzInstanceFactory;
use Mapbender\XyzBundle\Component\XyzLayerRenderer;
use Mapbender\XyzBundle\Component\XyzLoader;
use Mapbender\XyzBundle\Entity\XyzSource;

class XyzDataSource extends DataSource
{
    const TYPE = "xyz";

    public function __construct(
        private XyzConfigGenerator  $configService,
        private XyzInstanceFactory  $instanceFactory,
        private XyzLoader           $loader,
        private XyzLayerRenderer    $layerRenderer,
    )
    {
    }

    public function getName(): string
    {
        return self::TYPE;
    }

    public function getLabel(bool $compact = false): string
    {
        return "XYZ Tiles";
    }

    public function getConfigGenerator(): XyzConfigGenerator
    {
        return $this->configService;
    }

    public function getInstanceFactory(): XyzInstanceFactory
    {
        return $this->instanceFactory;
    }

    public function getLoader(): XyzLoader
    {
        return $this->loader;
    }

    public function getLayerRenderer(): XyzLayerRenderer
    {
        return $this->layerRenderer;
    }

    public function getSourceEntityClass(): string
    {
        return XyzSource::class;
    }

    public function getMetadataBackendTemplate(): ?string
    {
        return '@MapbenderXyz/view.html.twig';
    }

    public function getEntityTypeDiscriminator(): string
    {
        return self::TYPE;
    }
}
