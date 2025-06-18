<?php


namespace Mapbender\CoreBundle\Component\Source;


use Mapbender\CoreBundle\Entity\Source;

/**
 * The service that is responsible for creating Source objects, primarily used when adding or
 * refreshing sources in the backend.
 */
abstract class SourceLoader
{
    /**
     * Fully qualified class name of the form type used to create or refresh this source.
     * Should be a subclass of @see \Mapbender\ManagerBundle\Form\Type\SourceType
     */
    abstract public function getFormType(): string;

    /**
     * Called when a new source should be created. $formData contains the data submitted by the user.
     * The data type will depend on the form type's `data_class` option. (@see self::getFormType())
     */
    abstract public function loadSource(mixed $formData): Source;

    /**
     * Called when an existing source should be edited or refreshed. $formData contains the data submitted by the user.
     * The data type will depend on the form type's `data_class` option. (@see self::getFormType())
     */
    public function refreshSource(Source $source, mixed $formData): void
    {

    }

    /**
     * Gets the model data that will be passed to the refresh form. This may or may not be the source itself.
     * The return type should match the data type of the form type's `data_class` option.
     */
    public function getRefreshModel(Source $source): mixed
    {
        return $source;
    }
}
