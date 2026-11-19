<?php


namespace Mapbender\CoreBundle\Component\Source;


use Mapbender\CoreBundle\Entity\Application;
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
     * @return Source|null Returns the default source model for this data source type that will be used in the form
     * type when creating a new source. May be null if no fields should have default values
     */
    public function getDefaultModel(): ?Source
    {
        return null;
    }

    /**
     * Returns the twig template for creating/refreshing a Source in the manager.
     * Should extend @MapbenderManager/layouts/single_form.html.twig
     * @param bool $forReload True if the form is for refreshing an existing source, false if creating a new source
     */
    public function getFormTemplate(bool $forReload, ?Source $source): string
    {
        return $forReload
            ? '@MapbenderManager/Source/reload.html.twig'
            : '@MapbenderManager/Source/add.html.twig';
    }

    /**
     * Provides additional variables to feed into @see self::getFormTemplate()
     */
    public function getFormTemplateVariables(bool $forReload, ?Source $source): array
    {
        return [];
    }

    /**
     * Gets the model data that will be passed to the refresh form. This may or may not be the source itself.
     * The return type should match the data type of the form type's `data_class` option.
     */
    public function getRefreshModel(Source $source): mixed
    {
        return $source;
    }

    /**
     * Determines if @see loadSource is called before called reading metadata.
     * If there is persisted metadata (method returns true), loadSource won't be called
     */
    public function hasPersistedMetadata(Application $application, Source $source): bool
    {
        return true;
    }
}
