<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\PrintBundle\Component\LayerRenderer;

abstract class DataSource
{

    public abstract function getName(): string;

    public abstract function getLabel(): string;

    public abstract function getSourceEntityClass(): string;

    public abstract function getConfigGenerator(): SourceInstanceConfigGenerator;

    public abstract function getInstanceFactory(): SourceInstanceFactory;

    public abstract function getLoader(): SourceLoader;

    public abstract function getLayerRenderer(): LayerRenderer;

    public function allowAddSourceFromManager(): bool
    {
        return true;
    }

    public function getTypeDiscriminator(): string
    {
        return strtolower($this->getName())."source";
    }

    public function getMetadataFrontendTemplate(): ?string
    {
        return null;
    }

    public function getMetadataBackendTemplate(): ?string
    {
        return '@MapbenderManager/Repository/source/view.html.twig';
    }

}
