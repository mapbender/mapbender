# Custom Sources - Backend

This tutorial describes how to create a new data source that can be configured in the backoffice. 

## Create a new data source class

The heart of the new data source is a class that extends the `Mapbender\CoreBundle\Component\Source\DataSource` class.

Analogous to templates and elements, the new data source can be registered by tagging the class with `mapbender.datasource` in the service definition.


Option 1: Using Autowiring
```php
#[AutoconfigureTag("mapbender.datasource")]
class VectorTilesDataSource extends DataSource
{ ... }
```

Option 2: Using XML configuration
```xml
<service id="mapbender.source.mynewsource" class="Mapbender\MyNewSourceBundle\MyNewDataSource">
    <tag name="mapbender.datasource" />
    <argument type="service" id=" ... " />
</service>
```

To increase readability and not have one god class for all data source-related tasks, the data source class is split into several components, each responsible for a specific task: Source loading, instance creation, config generation, and layer rendering. They are each explained in detail below.

The class requires the following methods to be implemented:

- `getName(): string` - a globally unique (ignoring case) name/identifier for this source type. Should not contain spaces or special characters.
- `getLabel(bool $compact = false): string` - a human-readable label for this source type, e.g. "WMS" or "WFS". The compact flag will be set e.g. in the layerset list of an application where space is limited. Should be localised if possible and reasonable (inject the `translator` service or `TranslatorInterface` class for that purpose).
- `getSourceEntityClass(): string` - The fully qualified class name of the source entity for this data source. Should be a subclass of `\Mapbender\CoreBundle\Entity\Source` (see [below](#entity-class)).
- `getLoader(): SourceLoader` - The service that is responsible for creating Source objects, primarily used when adding or refreshing sources in the backend. (see [below](#sourceloader))
- `getInstanceFactory(): SourceInstanceFactory` - The factory responsible for creating SourceInstance objects, primarily used to add instances of sources to an application. (see [below](#sourceinstancefactory))
- `getConfigGenerator(): SourceInstanceConfigGenerator` - The service responsible for collecting information for frontend rendering (see [below](#configgenerator))
- `getLayerRenderer(): LayerRenderer` - The service responsible for rendering this data source to a canvas, mainly for print and image export (see [below](#layerrenderer))

The following optional methods can be overridden to provide additional functionality:

- `allowAddSourceFromManager(): bool` - Determines whether this source appears in the "Add source" dropdown in the manager. Defaults to `true`.
- `getEntityTypeDiscriminator(): string` - The globally unique discriminator for this source type, used to identify the source type in the database. Default: lowercase name of the source type, suffixed with "source".
- `getMetadataFrontendTemplate(): ?string` - The template rendered when the metadata for this source is requested using the layertree's metadata context menu option.  Defaults to `null`, which means no metadata is available for this data source.
- `getMetadataBackendTemplate(): ?string` - The template rendered when viewing source details in the manager. Defaults to `@MapbenderManager/Repository/source/view.html.twig` which shows default metadata like e.g. title, version, the applications the source is currently used in. When providing a custom template, it's recommended to extend the default template and add additional information to it, rather than replacing it entirely.

## Entity Class

A data source requires at least two entities: The `Source` entity, and the `SourceInstance` entity, which represents a specific instance of a data source in an application.

Each data source is saved in its own database table, since each data source requires different configuration options and therefore options. However, some options are shared between all data sources, such as the title and description. This is implemented using a 
doctrine [`MappedSuperclass`](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/inheritance-mapping.html#mapped-superclasses). A data source entity class should extend from either `Mapbender\CoreBundle\Entity\Source` or `Mapbender\CoreBundle\Entity\HttpParsedSource` if the source is built upon parsing a URL with optional Basic Auth authentication.

The entity class should be marked as `#[ORM\Entity]` and `#[ORM\Table]`. Apart from source-specific options, it should also implement the following fields and methods:

- `getInstances(): Collection|array` : Should provide a OneToMany association to the `SourceInstance` entity class, e.g. `#[ORM\OneToMany(mappedBy: 'source', targetEntity: MyNewDataSourceInstance::class, cascade: ['remove'])] protected $instances`
- `getLayers(): array|Collection`: If the source consists of sublayers that should also be togglable via the layertree, create a separate entity for them extending `\Mapbender\CoreBundle\Entity\SourceItem` and return them here. If it does not, simply return an empty array.

The source instance class should also be marked as entity and table. It should extend from `Mapbender\CoreBundle\Entity\SourceInstance` and implement the following methods:

- `getSource(): Source` / `setSource(Source $source)` - Should provide a ManyToOne association to the `Source` entity class, e.g.

```php
#[ORM\ManyToOne(targetEntity: MyNewDataSource::class, cascade: ['refresh'], inversedBy: 'instances')]
#[ORM\JoinColumn(name: 'source', referencedColumnName: 'id', onDelete: 'CASCADE')]
protected $source;
```

- `getLayers(): array|Collection`: If the source consists of sublayers that should also be togglable via the layertree, create a separate entity for them extending `\Mapbender\CoreBundle\Entity\SourceInstanceItem` and return the instance layers here. If it does not, simply return an empty array.
- `getDisplayTitle(): string` - Returns the title of the source instance as displayed in the backoffice metadata view.

The instance can additionally implement the `SupportsProxy` interface to support proxying, and the `SupportsOpacity` interface to support opacity. If it does, also create the corresponding columns (`#[ORM\Column(type: 'boolean', nullable: true)] protected $proxy = false;`, `#[ORM\Column(type: 'integer', nullable: true)] protected $opacity = 100;`)

## SourceLoader

The SourceLoader should extend `Mapbender\CoreBundle\Component\Source\SourceLoader`. It is responsible for creating Source objects, primarily used when adding or refreshing sources in the backend.

The class requires the following methods to be implemented:

- `getFormType(): string`: Fully qualified class name of the form type used to create or refresh this source. Should be a subclass of `\Mapbender\ManagerBundle\Form\Type\SourceType`. If the soruce requires an url to entered along with optional basic auth credentials, `\Mapbender\ManagerBundle\Form\Type\HttpSourceSelectionType` can be used.
- `loadSource(mixed $formData): Source`: Called when a new source should be created. $formData contains the data submitted by the user. The data type will depend on the form type's `data_class` option.

The following optional methods can be overridden to provide additional functionality:

- `refreshSource(Source $source, mixed $formData): void`: Called when an existing source should be edited or refreshed. $formData contains the data submitted by the user. The data type will depend on the form type's `data_class` option. The default implementation is empty.
- `getRefreshModel(Source $source): mixed`: Gets the model data that will be passed to the refresh form. This may or may not be the source itself (default behaviour is the source itself). The return type should match the data type of the form type's `data_class` option

## SourceInstanceFactory

The SourceInstanceFactory should extend `Mapbender\CoreBundle\Component\Source\SourceInstanceFactory`. It is the factory for SourceInstance objects, primarily used to add instances of sources to an application. It is also used to create instances from YAML-defined applications.

The class requires the following methods to be implemented:

- `createInstance(Source $source, ?array $options = null): SourceInstance`: Create a new SourceInstance entity of the given source (for db applications) as well as instances of `SourceInstanceItem` if manual layer control is required, and set all default options. You don't need to persist the instance in doctrine, this is done by the caller.
- `fromConfig(array $data, string $id): SourceInstance`: Create a (non-persisted) SourceInstance from a YAML configuration. Use the supplied $id as the instance's id and as instance layer id prefix.
- `matchInstanceToPersistedSource(SourceInstance $instance, array $extraSources): ?Source`: Swaps an ephemeral Source (plus layers) on a SourceInstance for an already db-persisted Source. This is used when importing YAML-defined applications to db, to avoid persisting duplicate equivalent Source entities.

The following optional methods can be overridden to provide additional functionality:

- `getFormType(SourceInstance $instance): string`: Returns the fully qualified class name of the form type used to edit this SourceInstance. Should inherit from `\Mapbender\ManagerBundle\Form\Type\SourceInstanceType`, which is also the default. SourceInstanceType already provides options for title, basesources and proxy (if the InstanceEntity implements the `SupportsProxy` interface) as well as opacity (if the InstanceEntity implements the `SupportsOpacity` interface)
- `getFormTemplate(SourceInstance $instance): string`: Returns the twig template for editing this SourceInstance in the manager. Should extend (and defaults to) `@MapbenderManager/Repository/instance.html.twig`.
- `canDeactivateLayer(SourceInstanceItem $instanceItem): bool`: Returns whether an instance layer can be disabled in the wms edit screen. Only relevant for sources that offer sublayers. Defaults to true


## ConfigGenerator

The ConfigGenerator should extend `Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator` and is responsible for frontend-facing configuration for SourceInstance entities. It is called by the ConfigController.

The class requires the following method to be implemented:
 
- `getScriptAssets(Application $application): array`: Should return a list of references to Javascript files that are required for the frontend to render this data source. Use the bundle reference syntax, e.g. `@MyNewSourceBundle/Resources/public/mapbender.mynewsource.js`

The following optional methods can be overridden to provide additional functionality:

- `getConfiguration(SourceInstance $sourceInstance): array`: Produces serializable frontend configuration. Per default, it returns the instance's id, type, title and the information whether it is a basesource. Extend this method to provide all information the frontend needs to render this source instance. The returned array will be JSON-encoded and sent to the frontend.
- `isInstanceEnabled(SourceInstance $sourceInstance): bool`: Determines whether the source instance is enabled in the frontend. Defaults to the `enabled` property of the source instance, but can be overridden to provide additional logic.
- `useTunnel(SourceInstance $sourceInstance): bool`: returns if this SourceInstance should be loaded using a proxy tunnel. Defaults to `false`.
- `getInternalLegendUrl(SourceInstanceItem $instanceLayer): ?string`: Non-public legend url for tunneled instance


## LayerRenderer

The LayerRenderer should extend `Mapbender\PrintBundle\Component\LayerRenderer` and is responsible for rendering the data source to a canvas, mainly for print and image export.

The class requires the following methods to be implemented:

- `addLayer(ExportCanvas $canvas, array $layerDef, Box $extent): void`: Should render the image modeled by the given $layerDef array onto the given $canvas using Gd image functions.
- `squashLayerDefinitions(array $layerDef, array $nextLayerDef, Resolution $resolution): array|false`: Receives two array-formatted rendering layer definitions. If a more efficient single layer definition exists, this method should create and return it. Otherwise, it should return false.

[↑ Back to top](#custom-sources---backend)

[← Back to README](../README.md)
