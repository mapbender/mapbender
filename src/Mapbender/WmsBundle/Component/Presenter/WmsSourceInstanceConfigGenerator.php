<?php


namespace Mapbender\WmsBundle\Component\Presenter;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Component\MinMax;
use Mapbender\WmsBundle\Component\Style;
use Mapbender\WmsBundle\Component\VendorSpecificHandler;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Instance registered in container at mapbender.source.wms.config_generator
 * see services.xml
 * @phpstan-type WmsInstanceLayerArray array{id: int|string, active: bool, legendEnabled: bool, selected: bool, allowinfo: bool, allowselected: bool, allowtoggle: bool, toggle: bool, title: ?string, info: bool, style: ?string, priority: int, parentId: null|int|string, sourceId: int|string, lsTitle: ?string, lsName: ?string, lsLatLonBounds: null|BoundingBox, lsStyles: (null|Style[]), minScale: ?int, maxScale: ?int, lsScale: ?MinMax}
 */
class WmsSourceInstanceConfigGenerator extends SourceInstanceConfigGenerator
{

    public function __construct(
        protected UrlProcessor           $urlProcessor,
        protected TokenStorageInterface  $tokenStorage,
        protected EntityManagerInterface $em,
        protected ?string                $defaultLayerOrder)
    {
    }

    /**
     * preload and cache instance layers. If using getSublayer(), doctrine will
     * make a separate database query for each layer massively degrading performance
     * when many layers are present.
     * The key for the first level is the parent layer id, or null for root layers.
     * @var WmsInstanceLayerArray[]
     */
    protected array $preloadedLayersByParent = [];
    /** @var WmsInstanceLayerArray */
    protected array $preloadedLayersById = [];

    public function isInstanceEnabled(SourceInstance $sourceInstance): bool
    {
        /** @var WmsInstance $sourceInstance */
        $rootLayer = $this->getRootLayerFromCache($sourceInstance);
        return parent::isInstanceEnabled($sourceInstance) && $rootLayer;
    }

    /**
     * @param WmsInstance $sourceInstance
     * @return array
     */
    public function getConfiguration(SourceInstance $sourceInstance, ?string $idPrefix = null): array
    {
        $config = parent::getConfiguration($sourceInstance);

        $root = $this->getRootLayerFromCache($sourceInstance);
        if (!$root) {
            throw new \RuntimeException("Cannot process Wms instance #{$sourceInstance->getId()} with no root layer");
        }

        $config = array_merge($config, [
            'title' => $root['title'] ?: $root['lsTitle'] ?: $sourceInstance->getTitle(),
            'options' => $this->getOptionsConfiguration($sourceInstance),
            'children' => array(
                $this->getLayerConfiguration($sourceInstance, $root, $idPrefix),
            ),
        ]);

        return $this->postProcessUrls($sourceInstance, $config);
    }

    public function getOptionsConfiguration(WmsInstance $sourceInstance): array
    {
        $buffer = max(0, intval($sourceInstance->getBuffer()));
        $ratio = $sourceInstance->getRatio();
        if ($ratio !== null) {
            $ratio = floatval($ratio);
        }

        return array(
            'url' => $this->getUrlOption($sourceInstance),
            'opacity' => ($sourceInstance->getOpacity() / 100),
            'proxy' => $this->useProxy($sourceInstance),
            'version' => $sourceInstance->getSource()->getVersion(),
            'format' => $sourceInstance->getFormat(),
            'info_format' => $sourceInstance->getInfoformat(),
            'exception_format' => $sourceInstance->getExceptionformat(),
            'transparent' => $sourceInstance->getTransparency(),
            'tiled' => $sourceInstance->getTiled(),
            'bbox' => $this->getBboxConfiguration($sourceInstance),
            'dimensions' => $this->getDimensionsConfiguration($sourceInstance),
            'buffer' => $buffer,
            'ratio' => $ratio,
            'refreshInterval' => $sourceInstance->getRefreshInterval(),
            'layerOrder' => $sourceInstance->getLayerOrder(),
        );
    }

    /**
     * @param WmsInstanceLayerArray $instanceLayer
     */
    protected function getLayerConfiguration(WmsInstance $instance, array $instanceLayer, ?string $idPrefix): array
    {
        $configuration = array(
            "options" => $this->getLayerOptionsConfiguration($instance, $instanceLayer, $idPrefix),
            "state" => array(
                "visibility" => null,
                "info" => null,
                "outOfScale" => null,
                "outOfBounds" => null,
            ),
        );
        $children = array();
        foreach ($this->getSublayersFromCache($instanceLayer) as $childLayer) {
            if ($childLayer['active']) {
                $children[] = $this->getLayerConfiguration($instance, $childLayer, $idPrefix);
            }
        }
        if ($children) {
            $layerOrder = $instance->getLayerOrder()
                ?: $this->defaultLayerOrder
                    ?: WmsInstance::LAYER_ORDER_TOP_DOWN;
            if ($layerOrder == WmsInstance::LAYER_ORDER_BOTTOM_UP) {
                $children = array_reverse($children);
            }
            $configuration['children'] = $children;
        }
        return $configuration;
    }

    /**
     * @param WmsInstanceLayerArray $layer
     * @return array
     */
    protected function getLayerOptionsConfiguration(WmsInstance $instance, array $layer, ?string $idPrefix): array
    {
        $styles = $this->getAvailableStyles($instance, $layer);
        if ($layer['legendEnabled'] === false) {
            foreach ($styles as $style) {
                /** @var Style $style */
                $style->setLegendUrl(null);
            }
        }
        $configuration = array(
            "id" => ($idPrefix ?? '') . $layer['id'],
            "priority" => $layer['priority'],
            "name" => strval($layer['lsName']),
            "title" => $layer['title'] ?: $layer['lsTitle'],
            "queryable" => $layer['info'],
            "style" => $layer['style'],
            "minScale" => $this->getInheritedScale('minScale', $layer),
            "maxScale" => $this->getInheritedScale('maxScale', $layer),
            "bbox" => $this->getLayerBboxConfiguration($layer),
            "treeOptions" => $this->getTreeOptionsLayerConfig($layer),
            "metadataUrl" => $this->getMetadataUrl($instance, $layer),
            "availableStyles" => $styles,
        );
        $configuration += array_filter(array(
            'legend' => $this->getLegendConfig($instance, $layer),
        ));
        return $configuration;
    }

    /**
     * @param WmsInstanceLayerArray $instanceLayer
     */
    protected function getMetadataUrl(WmsInstance $instance, array $instanceLayer): ?string
    {
        // no metadata for unpersisted instances (WmsLoader)
        if (!$instanceLayer['id']) {
            return null;
        }
        $layerset = $instance->getLayerset();
        if ($layerset && $layerset->getApplication() && !$layerset->getApplication()->isDbBased()) {
            return null;
        }
        $router = $this->urlProcessor->getRouter();
        return $router->generate('mapbender_core_application_metadata', array(
            'instance' => $instance,
            'layerId' => $instanceLayer['id'],
        ));
    }

    /**
     * @param WmsInstanceLayerArray $instanceLayer
     */
    protected function getTreeOptionsLayerConfig(array $instanceLayer): array
    {
        $hasChildren = !!count($this->getSublayersFromCache($instanceLayer));
        return array(
            "info" => $instanceLayer['info'],
            "selected" => $instanceLayer['selected'],
            "toggle" => $hasChildren ? $instanceLayer['toggle'] : null,
            "allow" => array(
                "info" => $instanceLayer['allowinfo'],
                "selected" => $instanceLayer['allowselected'],
                "toggle" => $hasChildren ? $instanceLayer['allowtoggle'] : null,
            ),
        );
    }

    /**
     * @param WmsInstance $sourceInstance
     * @param mixed[] $configuration
     * @return mixed[] modified configuration
     * @todo: tunnel vs no-tunnel based on "sensitive" VendorSpecifics may not be cachable, investigate
     *
     */
    public function postProcessUrls(WmsInstance $sourceInstance, $configuration)
    {
        if ($this->useTunnel($sourceInstance)) {
            $url = $this->urlProcessor->getPublicTunnelBaseUrl($sourceInstance);
            $configuration['options']['url'] = $url;
            // remove ows proxy for a tunnel connection
            $configuration['options']['tunnel'] = true;
        } else {
            if ($this->useProxy($sourceInstance)) {
                $configuration['options']['url'] = $this->urlProcessor->proxifyUrl($configuration['options']['url']);
                $configuration['children'][0] = $this->proxifyLayerUrls($configuration['children'][0], $sourceInstance);
            } else {
                // Don't proxify, but do provide signature to allow OpenLayers to bypass CORB
                $configuration['options']['url'] = $this->urlProcessor->signUrl($configuration['options']['url']);
            }
        }
        return $configuration;
    }

    /**
     * Return the source instance's base url extended with (potentially dynamic, user dependent) params
     * from dimensions and public vendor specifics.
     *
     * @param WmsInstance $sourceInstance
     * @return string
     */
    public function getUrlOption(WmsInstance $sourceInstance)
    {
        $url = $sourceInstance->getSource()->getGetMap()->getHttpGet();
        if (!$this->useTunnel($sourceInstance)) {
            // WmsLoader special: public username + password transmission
            $originUrl = $sourceInstance->getSource()->getOriginUrl();
            $originHasCredentials = !!\parse_url($originUrl, PHP_URL_USER);
            $getMapHasCredentials = !!\parse_url($url, PHP_URL_USER);
            if ($originHasCredentials && !$getMapHasCredentials) {
                $username = \urldecode(\parse_url($originUrl, PHP_URL_USER));
                $password = \urldecode(\parse_url($originUrl, PHP_URL_PASS) ?: '');
                $url = UrlUtil::addCredentials($url, $username, $password);
            }
        }
        $userToken = $this->tokenStorage->getToken();
        $vsHandler = new VendorSpecificHandler();
        $params = $vsHandler->getPublicParams($sourceInstance, $userToken);
        return UrlUtil::validateUrl($url, $params);
    }

    /**
     * Return an array mapping srs id : bounding box coordinates
     *
     * @param WmsInstance $sourceInstance
     * @return float[][]
     */
    public function getBboxConfiguration(WmsInstance $sourceInstance)
    {
        $rootLayer = $this->getRootLayerFromCache($sourceInstance);
        return $this->getLayerBboxConfiguration($rootLayer);
    }

    /**
     * @param WmsInstanceLayerArray $layer
     * @return float[][]
     */
    protected function getLayerBboxConfiguration(array $layer): array
    {
        $configs = [];
        $bbox = $layer["lsLatLonBounds"];
        if ($bbox) {
            $configs[$bbox->getSrs()] = $bbox->toCoordsArray();
        }
        return $configs;
    }

    /**
     * @param "minScale"|"maxScale" $key
     * @param WmsInstanceLayerArray $layer
     * Array reimplementation of @see WmsInstanceLayer::getInheritedMinScale()
     * Get inherited effective min scale for layer instance
     * 1) if source layer has a non-null value, use that
     * 2) if admin replaced min scale for the parent layer instance, use that value.
     * 3) if neither is set, recurse up, maintaining preference source layer first, then parent instance layer
     */
    protected function getInheritedScale(string $key, array $layer): ?int
    {
        while ($layer) {
            if ($layer[$key]) return $layer[$key];
            if ($layer['lsScale']) {
                if ($key === 'minScale' && $layer['lsScale']->getMin()) return $layer['lsScale']->getMin();
                if ($key === 'maxScale' && $layer['lsScale']->getMax()) return $layer['lsScale']->getMax();
            }
            if (!$layer['parentId']) return null;
            $layer = $this->preloadedLayersById[$layer['parentId']] ?? null;
        }
        return null;
    }

    /**
     * @param WmsInstanceLayerArray $layer
     * @return Style[]
     * partial reimplementation of WmsLayerSource::getStyles() since we don't have the object here for performance reasons
     * @see WmsLayerSource::getStyles()
     */
    protected function getAvailableStyles(WmsInstance $instance, array $layer): array
    {
        $styles = [];
        // store "hash" of style by using name and title
        foreach ($layer['lsStyles'] ?? [] as $style) {
            /** @var Style $style */
            $legendHref = $style->getLegendUrl()?->getOnlineResource()?->getHref();
            if ($legendHref) {
                $proxifiedUrl = $this->proxifyLegendUrl($instance, $layer, $legendHref);
                $style->getLegendUrl()->getOnlineResource()->setHref($proxifiedUrl);
            }
            $styles[$style->getName() . "|" . $style->getTitle()] = $style;
        }

        while ($layer['parentId']) {
            $layer = $this->preloadedLayersById[$layer['parentId']] ?? null;
            if (!$layer) break;
            foreach ($layer['lsStyles'] ?? [] as $style) {
                $hash = $style->getName() . "|" . $style->getTitle();
                $styles[$hash] = $style;
            }

        }
        return array_values($styles);
    }

    /**
     * Return the collected configuration arrays from all Dimensions on the given $sourceInstance
     *
     * @param WmsInstance $sourceInstance
     * @return array[]
     */
    public function getDimensionsConfiguration(WmsInstance $sourceInstance)
    {
        $dimensionConfigs = array();
        $sourceDimensions = array();
        foreach ($sourceInstance->getSource()->getDimensions() as $sourceDimension) {
            $sourceDimensions[$sourceDimension->getName()] = $sourceDimension;
        }

        foreach ($sourceInstance->getDimensions() as $dimensionInstance) {
            if ($dimensionInstance->getActive() && !empty($sourceDimensions[$dimensionInstance->getName()])) {
                $sourceDimension = $sourceDimensions[$dimensionInstance->getName()];
                $dimensionConfigs[] = array(
                    // Instance-editables
                    'default' => $dimensionInstance->getDefault(),
                    'extent' => DimensionInst::getData($dimensionInstance->getExtent()),
                    // Magic auto-inferred type, still used by some client-side code
                    'type' => DimensionInst::findType($dimensionInstance->getExtent()),
                    // Rest from source dimension
                    'name' => $sourceDimension->getName(),
                    '__name' => $sourceDimension->getParameterName(),
                    'current' => $sourceDimension->getCurrent(),
                    'multipleValues' => $sourceDimension->getMultipleValues(),
                    'nearestValue' => $sourceDimension->getNearestValue(),
                    'unitSymbol' => $sourceDimension->getUnitSymbol(),
                    'units' => $sourceDimension->getUnits(),
                );
            }
        }
        return $dimensionConfigs;
    }

    /**
     * @param WmsInstanceLayerArray $instanceLayer
     */
    public function getLegendConfig(WmsInstance $instance, array $instanceLayer): array
    {
        if (!$instanceLayer['legendEnabled']) {
            return array();
        }

        $legendUrl = $this->getInternalLegendUrl($instanceLayer);

        // HACK for reusable source instances: suppress / skip url generation if instance is not owned by a Layerset
        // @todo: implement legend url generation for reusable instances
        if ($legendUrl) {
            $publicLegendUrl = $this->proxifyLegendUrl($instance, $instanceLayer, $legendUrl);
            return array(
                "url" => $publicLegendUrl,
            );
        }
        return array();
    }

    private function proxifyLegendUrl(WmsInstance $instance, array $instanceLayer, string $legendUrl): string
    {
        if ($this->useTunnel($instance)) {
            // request via tunnel, see ApplicationController::instanceTunnelLegendAction
            $tunnelService = $this->urlProcessor->getTunnelService();
            $publicLegendUrl = $tunnelService->generatePublicLegendUrl($instanceLayer, $instance, $legendUrl);
        } else {
            $publicLegendUrl = $legendUrl;
        }
        return $publicLegendUrl;
    }

    /**
     * @param WmsInstanceLayer|WmsInstanceLayerArray $instanceLayer
     * @return string|null
     */
    public function getInternalLegendUrl(SourceInstanceItem|array $instanceLayer): ?string
    {
        // scan styles for legend url entries backwards
        // some WMS services may not populate every style with a legend, so just checking the last
        // style for a legend is not enough
        // @todo: style node selection should follow configured style
        if (is_array($instanceLayer)) {
            $styles = $instanceLayer['lsStyles'] ?? [];
        } else {
            $layerSource = $instanceLayer->getSourceItem();
            $styles = array_reverse($layerSource->getStyles(false));
        }

        foreach ($styles as $style) {
            /** @var Style $style */
            $legendUrl = $style->getLegendUrl();
            if ($legendUrl) {
                return $legendUrl->getOnlineResource()->getHref();
            }
        }
        return null;
    }

    /**
     * Checks if service has auth information that needs to be hidden from client.
     */
    public function useTunnel(SourceInstance $sourceInstance): bool
    {
        if ($sourceInstance->getId()) {
            /** @var WmsInstance $sourceInstance */
            $vsHandler = new VendorSpecificHandler();
            return (!!$sourceInstance->getSource()->getUsername()) || $vsHandler->hasHiddenParams($sourceInstance);
        } else {
            // dynamically added (~WmsLoader)
            return false;
        }
    }

    public function useProxy(WmsInstance $sourceInstance): bool
    {
        if ($this->useTunnel($sourceInstance)) {
            return false;
        } else {
            if ($sourceInstance->isProtectedDynamicWms()) {
                // WmsLoader special: proxify url with embedded credentials to bypass browser
                // filtering of basic auth in img tags.
                // see https://stackoverflow.com/questions/3823357/how-to-set-the-img-tag-with-basic-authentication
                return true;
            } else {
                /** @var WmsInstance $sourceInstance */
                return $sourceInstance->getProxy();
            }
        }
    }

    /**
     * Extend all URLs in the layer to run over owsproxy
     * @param mixed[] $layerConfig
     * @return mixed[]
     * @todo: this should and can be part of the initial generation
     */
    protected function proxifyLayerUrls($layerConfig, ?SourceInstance $sourceInstance = null)
    {
        /** @var ?WmsInstance $sourceInstance */
        if (isset($layerConfig['children'])) {
            foreach ($layerConfig['children'] as $ix => $childConfig) {
                $layerConfig['children'][$ix] = $this->proxifyLayerUrls($childConfig, $sourceInstance);
            }
        }
        if (!empty($layerConfig['options']['legend']['url'])) {
            $url = $layerConfig['options']['legend']['url'];
            if ($sourceInstance->isProtectedDynamicWms() && !$sourceInstance->getSource()->getUsername()) {
                // for dynamically loaded WMS with password (WMSLoader), the legend url is read from GetCapabilities but
                // does not include the basic auth data. This results in the legend not being displayed.
                // As a workaround, insert the basic auth data manually.
                $url = $this->injectBasicAuthData($url, $sourceInstance);
            }
            $layerConfig['options']['legend']['url'] = $this->urlProcessor->proxifyUrl($url);
        }
        if (is_array($layerConfig['options']['availableStyles'])) {
            foreach ($layerConfig['options']['availableStyles'] as $style) {
                /** @var $style Style */
                if (!$style->getLegendUrl()) {
                    continue;
                }
                $resource = $style->getLegendUrl()->getOnlineResource();
                $url = $resource->getHref();
                if ($sourceInstance->isProtectedDynamicWms() && !$sourceInstance->getSource()->getUsername()) {
                    // for dynamically loaded WMS with password (WMSLoader), the legend url is read from GetCapabilities but
                    // does not include the basic auth data. This results in the legend not being displayed.
                    // As a workaround, insert the basic auth data manually.
                    $url = $this->injectBasicAuthData($url, $sourceInstance);
                }
                $resource->setHref($this->urlProcessor->proxifyUrl($url));
            }
        }
        return $layerConfig;
    }

    public function getAssets(Application $application, string $type): array
    {
        if ($type !== 'js') {
            return [];
        }
        return [
            '@MapbenderCoreBundle/Resources/public/mapbender.geosource.js',
            '@MapbenderWmsBundle/Resources/public/mapbender.geosource.wms.js',
        ];
    }

    protected function injectBasicAuthData(?string $sourceUrl, ?SourceInstance $sourceInstance): string
    {
        if ($sourceInstance === null) return $sourceUrl;
        $originUrl = $sourceInstance->getSource()->getOriginUrl();

        // if the legend url has basic auth data already, just return it
        if (preg_match('/^(https?:\/\/)([^@]+)@/', $sourceUrl)) {
            return $sourceUrl;
        }

        // Regex to extract the auth info (user:pass) from the source URL
        preg_match('/^(https?:\/\/)([^@]+)@/', $originUrl, $sourceMatches);

        // If the origin url doesn't have authentication info, return the source url unchanged
        if (empty($sourceMatches) || !isset($sourceMatches[2])) {
            return $sourceUrl;
        }

        $authInfo = $sourceMatches[2];

        return preg_replace('/^(https?:\/\/)/', '${1}' . $authInfo . '@', $sourceUrl);

    }

    /**
     * preload and cache instance layers. If using getSublayer(), doctrine will
     * make a separate database query for each layer massively degrading performance
     * when many layers are present.
     */
    public function preload(array $sourceInstances): void
    {
        $allLayers = $this->em->createQueryBuilder()
            ->select('l.id, l.active, l.selected, l.toggle, l.legend AS legendEnabled, l.allowinfo, l.allowselected, l.allowtoggle, l.title, l.info, l.style, l.priority, l.minScale, l.maxScale, p.id AS parentId, i.id AS sourceId, ls.title AS lsTitle, ls.name AS lsName, ls.latlonBounds AS lsLatLonBounds, ls.styles AS lsStyles, ls.scale AS lsScale')
            ->from(WmsInstanceLayer::class, 'l')
            ->leftJoin(WmsInstanceLayer::class, 'p', 'WITH', 'l.parent = p.id')
            ->leftJoin(WmsInstance::class, 'i', 'WITH', 'l.sourceInstance = i.id')
            ->leftJoin(WmsLayerSource::class, 'ls', 'WITH', 'l.sourceItem = ls.id')
            ->where('l.sourceInstance IN (:instances)')
            ->setParameter('instances', $sourceInstances)
            ->orderBy('l.priority')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY)
        ;
        foreach ($allLayers as $layer) {
            /** @var WmsInstanceLayerArray $layer */
            $pid = $layer['parentId'];
            if (!array_key_exists($pid, $this->preloadedLayersByParent)) {
                $this->preloadedLayersByParent[$pid] = [];
            }
            $this->preloadedLayersByParent[$pid][] = $layer;
            $this->preloadedLayersById[$layer['id']] = $layer;
        }
    }

    /**
     * using the cached/preloaded data, replacement function for
     * @return WmsInstanceLayerArray[]
     * @see WmsInstanceLayer::getSublayer()
     */
    protected function getSublayersFromCache(array $parent): array
    {
        return $this->preloadedLayersByParent[$parent['id']] ?? [];
    }

    /**
     * @return ?WmsInstanceLayerArray
     */
    protected function getRootLayerFromCache(WmsInstance $parent, bool $preloadYamlIfNotFound = true): ?array
    {
        foreach (($this->preloadedLayersByParent[null] ?? []) as $layer) {
            if ($layer['sourceId'] === $parent->getId()) {
                return $layer;
            }
        }

        if ($preloadYamlIfNotFound) {
            $this->preloadLayersForYamlApplication($parent);
            return $this->getRootLayerFromCache($parent, false);
        }
        return null;
    }

    /**
     * for yaml applications or WMS Loader added layers, preload is not called
     * and we can't use doctrine to fetch layers in batch, so replicate the same array structure
     */
    private function preloadLayersForYamlApplication(WmsInstance $parent): void
    {
        foreach ($parent->getLayers() as $layer) {
            $pid = $layer->getParent()?->getId();
            $array = [
                "id" => $layer->getId(),
                "active" => $layer->getActive(),
                "selected" => $layer->getSelected(),
                "allowinfo" => $layer->getAllowInfo(),
                "allowselected" => $layer->getAllowSelected(),
                "allowtoggle" => $layer->getAllowToggle(),
                "toggle" => $layer->getToggle(),
                "title" => $layer->getTitle(),
                "info" => $layer->getInfo(),
                "style" => $layer->getStyle(),
                "legendEnabled" => $layer->getLegend(),
                "priority" => $layer->getPriority(),
                "parentId" => $pid,
                "sourceId" => $parent->getSource()->getId(),
                "lsTitle" => $layer->getSourceItem()->getTitle(),
                "lsName" => $layer->getSourceItem()->getName(),
                "lsLatLonBounds" => $layer->getSourceItem()->getLatLonBounds(),
                "lsStyles" => $layer->getSourceItem()->getStyles(),
                "minScale" => $layer->getMinScale(),
                "maxScale" => $layer->getMaxScale(),
                "lsScale" => $layer->getSourceItem()->getScale(),
            ];
            if (!array_key_exists($pid, $this->preloadedLayersByParent)) {
                $this->preloadedLayersByParent[$pid] = [];
            }
            $this->preloadedLayersByParent[$pid][] = $array;
            $this->preloadedLayersById[$array['id']] = $array;
        }
    }
}
