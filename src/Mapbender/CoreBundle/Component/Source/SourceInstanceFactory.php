<?php


namespace Mapbender\CoreBundle\Component\Source;


use Doctrine\ORM\Mapping\Entity;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\ManagerBundle\Component\Exchange\EntityPool;
use Mapbender\ManagerBundle\Component\Exchange\ImportState;
use Mapbender\ManagerBundle\Form\Type\SourceInstanceType;

/**
 * Factory for creating SourceInstance objects, primarily used to add instances of sources to an application.
 * It is also used to create instances from YAML-defined applications.
 */
abstract class SourceInstanceFactory
{
    /**
     * Create a new SourceInstance entity of the given source (for db applications)
     * @param array|null $options
     */
    abstract public function createInstance(Source $source, ?array $options = null): SourceInstance;

    /**
     * Create a (non-persisted) SourceInstance from a YAML configuration.
     * @param string $id used for instance and as instance layer id prefix
     */
    abstract public function fromConfig(array $data, string $id): SourceInstance;

    /**
     * Checks if there already is a db-persisted Source equivalent for an ephemeral source plus layers.
     * This is used when importing applications or duplicating YAML-defined applications to db, to avoid persisting
     * duplicate equivalent Source entities.
     * If there is a persisted source, return true and add the source as well as potential SourceLayers to the EntityPool.
     */
    abstract public function matchInstanceToPersistedSource(ImportState $importState, array $data, EntityPool $entityPool): bool;

    /**
     * Returns the fully qualified class name of the form type used to edit this SourceInstance.
     * Should inherit from @see \Mapbender\ManagerBundle\Form\Type\SourceInstanceType.
     */
    public function getFormType(SourceInstance $instance): string
    {
        return SourceInstanceType::class;
    }

    /**
     * Returns the twig template for editing this SourceInstance in the manager.
     * Should extend @MapbenderManager/Repository/instance.html.twig
     */
    public function getFormTemplate(SourceInstance $instance): string
    {
        return '@MapbenderManager/Repository/instance.html.twig';
    }

    /**
     * Returns whether an instance layer can be disabled in the wms edit screen. Only relevant for sources that offer sublayers.
     */
    public function canDeactivateLayer(SourceInstanceItem $instanceItem): bool
    {
        return true;
    }
}
