<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\PrintBundle\Component\LayerRenderer;

/**
 * Base class for mapbender data sources. Should be tagged as `mapbender.datasource`
 */
abstract class DataSource
{

    /**
     * a globally unique name for this source type. Should not contain spaces or special characters.
     */
    abstract public function getName(): string;

    /**
     * a human-readable label for this source type, e.g. "WMS" or "WFS".
     * The compact flag will be set e.g. in the layerset list of an application where space is limited.
     */
    abstract public function getLabel(bool $compact = false): string;

    /**
     * The service that is responsible for creating Source objects, primarily used when adding or
     * refreshing sources in the backend.
     */
    abstract public function getLoader(): SourceLoader;

    /**
     * The factory responsible for creating SourceInstance objects, primarily used to add instances
     * of sources to an application.
     */
    abstract public function getInstanceFactory(): SourceInstanceFactory;

    /**
     * The service responsible for collecting information for frontend rendering.
     */
    abstract public function getConfigGenerator(): SourceInstanceConfigGenerator;

    /**
     * The service responsible for rendering this data source to a canvas, mainly for print and image export
     */
    abstract public function getLayerRenderer(): LayerRenderer;

    /**
     * The fully qualified class name of the source entity for this data source. Should be a subclass of
     * @see \Mapbender\CoreBundle\Entity\Source
     */
    abstract public function getSourceEntityClass(): string;

    /**
     * Determines whether this source appears in the "Add source" dropdown in the manager.
     */
    public function allowAddSourceFromManager(): bool
    {
        return true;
    }

    /**
     * The globally unique discriminator for this source type, used to identify the source type in the database.
     * Default: lowercase name of the source type, suffixed with "source".
     */
    public function getEntityTypeDiscriminator(): string
    {
        return strtolower($this->getName()) . "source";
    }

    /**
     * The template rendered when the metadata for this source is requested using the layertree's metadata context menu option.
     */
    public function getMetadataFrontendTemplate(): ?string
    {
        return null;
    }

    /**
     * The template rendered when viewing source details in the manager
     */
    public function getMetadataBackendTemplate(): ?string
    {
        return '@MapbenderManager/Repository/source/view.html.twig';
    }

    /**
     * If true, Mapbender will not show URLs in the metadata output to
     * prevent leaking internal urls to end users. Will only be called
     * if the parameter `mapbender.show_proxied_metadata_urls` is false
     * (default value), otherwise URLs will always be shown.
     */
    public function areMetadataUrlsInternal(SourceInstance $instance): bool
    {
        return false;
    }

}
