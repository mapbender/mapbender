<?php


namespace Mapbender\CoreBundle\Component\Source;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\ManagerBundle\Form\Type\SourceInstanceType;

/**
 * Factory for creating SourceInstance objects, primarily used to add instances of sources to an application.
 * It is also used to create instances from YAML-defined applications.
 */
abstract class SourceInstanceFactory
{
    abstract public function createInstance(Source $source): SourceInstance;

    /**
     * @param string $id used for instance and as instance layer id prefix
     */
    abstract public function fromConfig(array $data, string $id): SourceInstance;

    /**
     * Swaps an ephemeral Source (plus layers) on a SourceInstance for an already db-persisted Source.
     * This is used when importing YAML-defined applications to db, to avoid persisting duplicate equivalent
     * Source entities.
     *
     * @param Source[] $extraSources
     */
    abstract public function matchInstanceToPersistedSource(SourceInstance $instance, array $extraSources): ?Source;

    public function getFormType(SourceInstance $instance): string
    {
        return SourceInstanceType::class;
    }

    public function getFormTemplate(SourceInstance $instance): string
    {
        return '@MapbenderManager/Repository/instance.html.twig';
    }
}
