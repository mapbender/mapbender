<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\PrintBundle\Component\LayerRenderer;

abstract class DataSource
{
    public abstract function getName(): string;

    public abstract function getConfigService(): SourceService;

    public abstract function getInstanceFactory(): SourceInstanceFactory;

    public abstract function getLoader(): SourceLoader;

    public abstract function getLayerRenderer(): LayerRenderer;

}
