<?php


namespace Mapbender\CoreBundle\Component\Source;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * Factory for creating SourceInstance objects, primarily used to add instances of sources to an application.
 * It is also used to create instances from YAML-defined applications.
 */
interface SourceInstanceFactory
{
    public function createInstance(Source $source): SourceInstance;

    /**
     * @param string $id used for instance and as instance layer id prefix
     */
    public function fromConfig(array $data, string $id): SourceInstance;

    /**
     * Swaps an ephemeral Source (plus layers) on a SourceInstance for an already db-persisted Source.
     * This is used when importing YAML-defined applications to db, to avoid persisting duplicate equivalent
     * Source entities.
     *
     * @param Source[] $extraSources
     */
    public function matchInstanceToPersistedSource(SourceInstance $instance, array $extraSources): ?Source;

    public function getFormType(SourceInstance $instance): string;

    public function getFormTemplate(SourceInstance $instance): string;
}
