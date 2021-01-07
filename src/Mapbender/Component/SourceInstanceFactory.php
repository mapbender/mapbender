<?php


namespace Mapbender\Component;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;

interface SourceInstanceFactory
{
    /**
     * @param Source $source
     * @return SourceInstance
     */
    public function createInstance(Source $source);

    /**
     * @param array $data
     * @param string $id used for instance and as instance layer id prefix
     * @return SourceInstance
     */
    public function fromConfig(array $data, $id);

    /**
     * Swaps an ephemeral Source (plus layers) on a SourceInstance for an already db-persisted Source.
     * This is used when importing YAML-defined applications to db, to avoid persisting duplicate equivalent
     * Source entities.
     *
     * @param SourceInstance $instance
     * @param Source[] $extraSources
     * @return Source|null
     */
    public function matchInstanceToPersistedSource(SourceInstance $instance, array $extraSources);

    /**
     * @param SourceInstance $instance
     * @return string
     */
    public function getFormType(SourceInstance $instance);

    /**
     * @param SourceInstance $instance
     * @return string
     */
    public function getFormTemplate(SourceInstance $instance);
}
