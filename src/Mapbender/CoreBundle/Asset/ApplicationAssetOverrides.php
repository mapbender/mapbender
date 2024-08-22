<?php


namespace Mapbender\CoreBundle\Asset;

/**
 * Allows to override specific resources
 *
 *
 * Example (add in your bundle class):
 *
 * public function boot(): void
    {
        parent::boot();
        $assetService = $this->container->get('mapbender.application_asset.overrides.service');
        $assetService->registerAssetOverride(
            '@MapbenderCoreBundle/Resources/public/sass/element/layertree.scss',
            '@MyBundle/Resources/public/element/custom_layertree.scss'
        );
    }
 */
class ApplicationAssetOverrides
{
    protected array $assetOverrideMap = [];

    public function __construct(?array $assetOverrides = null)
    {
        if (is_array($assetOverrides)) {
            $this->registerAssetOverrides($assetOverrides);
        }
    }

    /**
     * after calling this function, everytime $originalRef is requested, $newRef will be included instead
     * Can be used to override internal templates, javascript or css files
     * @see self::registerAssetOverrides() for registering multiple files at a time
     */
    public function registerAssetOverride(string $originalRef, string $newRef): void
    {
        $this->assetOverrideMap[$originalRef] = $newRef;
    }

    /**
     * after calling this function, everytime an asset that corresponds to a key in the overrideMap is requested,
     * it is replaced by the asset of the corresponding value
     * Can be used to override internal templates, javascript or css files
     */
    public function registerAssetOverrides(array $overrideMap): void
    {
        $this->assetOverrideMap = array_merge($this->assetOverrideMap, $overrideMap);
    }

    public function getMap(): array
    {
        return $this->assetOverrideMap;
    }
}
