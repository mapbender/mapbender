<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\PrintBundle\Component\LayerRenderer;

abstract class DataSource
{

    abstract public function getName(): string;

    abstract public function getLabel(bool $compact = false): string;

    abstract public function getSourceEntityClass(): string;

    abstract public function getConfigGenerator(): SourceInstanceConfigGenerator;

    abstract public function getInstanceFactory(): SourceInstanceFactory;

    abstract public function getLoader(): SourceLoader;

    abstract public function getLayerRenderer(): LayerRenderer;

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
