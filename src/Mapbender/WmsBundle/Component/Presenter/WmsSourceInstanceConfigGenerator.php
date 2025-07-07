<?php


namespace Mapbender\WmsBundle\Component\Presenter;

use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Component\Style;
use Mapbender\WmsBundle\Component\VendorSpecificHandler;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Instance registered in container at mapbender.source.wms.config_generator
 * see services.xml
 */
class WmsSourceInstanceConfigGenerator extends SourceInstanceConfigGenerator
{

    public function __construct(
        protected UrlProcessor          $urlProcessor,
        protected TokenStorageInterface $tokenStorage,
        protected ?string               $defaultLayerOrder)
    {
    }

    public function isInstanceEnabled(SourceInstance $sourceInstance): bool
    {
        /** @var WmsInstance $sourceInstance */
        $rootLayer = $sourceInstance->getRootlayer();
        return parent::isInstanceEnabled($sourceInstance) && $rootLayer;
    }

    public function canDeactivateLayer(SourceInstanceItem $layer): bool
    {
        /** @var WmsInstanceLayer $layer */
        // dissallow breaking entire instance by removing root layer
        return $layer->getSourceInstance()->getRootlayer() !== $layer;
    }

    /**
     * @param WmsInstance $sourceInstance
     * @return array
     */
    public function getConfiguration(SourceInstance $sourceInstance): array
    {
        $config = parent::getConfiguration($sourceInstance);

        $root = $sourceInstance->getRootlayer();
        if (!$root) {
            throw new \RuntimeException("Cannot process Wms instance #{$sourceInstance->getId()} with no root layer");
        }

        $config = array_merge($config, [
            'title' => $root->getTitle() ?: $root->getSourceItem()->getTitle() ?: $sourceInstance->getTitle(),
            'options' => $this->getOptionsConfiguration($sourceInstance),
            'children' => array(
                $this->getLayerConfiguration($sourceInstance->getRootlayer()),
            ),
        ]);

        return $this->postProcessUrls($sourceInstance, $config);
    }

    public function getOptionsConfiguration(WmsInstance $sourceInstance)
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
            'layerOrder' => $sourceInstance->getLayerOrder(),
        );
    }

    protected function getLayerConfiguration(WmsInstanceLayer $instanceLayer)
    {
        $configuration = array(
            "options" => $this->getLayerOptionsConfiguration($instanceLayer),
            "state" => array(
                "visibility" => null,
                "info" => null,
                "outOfScale" => null,
                "outOfBounds" => null,
            ),
        );
        $children = array();
        foreach ($instanceLayer->getSublayer() as $childLayer) {
            if ($childLayer->getActive()) {
                $children[] = $this->getLayerConfiguration($childLayer);
            }
        }
        if ($children) {
            $layerOrder = $instanceLayer->getSourceInstance()->getLayerOrder()
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
     * @param WmsInstanceLayer $instanceLayer
     * @return array
     */
    protected function getLayerOptionsConfiguration(WmsInstanceLayer $instanceLayer)
    {
        $sourceItem = $instanceLayer->getSourceItem();
        $configuration = array(
            "id" => strval($instanceLayer->getId()),
            "priority" => $instanceLayer->getPriority(),
            "name" => strval($sourceItem->getName()),
            "title" => $instanceLayer->getTitle() ?: $sourceItem->getTitle(),
            "queryable" => $instanceLayer->getInfo(),
            "style" => $instanceLayer->getStyle(),
            "minScale" => $instanceLayer->getMinScale(true),
            "maxScale" => $instanceLayer->getMaxScale(true),
            "bbox" => $this->getLayerBboxConfiguration($sourceItem),
            "treeOptions" => $this->getTreeOptionsLayerConfig($instanceLayer),
            'metadataUrl' => $this->getMetadataUrl($instanceLayer),
            'availableStyles' => $this->getAvailableStyles($sourceItem),
        );
        $configuration += array_filter(array(
            'legend' => $this->getLegendConfig($instanceLayer),
        ));
        return $configuration;
    }

    /**
     * @param WmsInstanceLayer $instanceLayer
     * @return string|null
     */
    protected function getMetadataUrl(WmsInstanceLayer $instanceLayer)
    {
        // no metadata for unpersisted instances (WmsLoader)
        if (!$instanceLayer->getId()) {
            return null;
        }
        $layerset = $instanceLayer->getSourceInstance()->getLayerset();
        if ($layerset && $layerset->getApplication() && !$layerset->getApplication()->isDbBased()) {
            return null;
        }
        $router = $this->urlProcessor->getRouter();
        return $router->generate('mapbender_core_application_metadata', array(
            'instance' => $instanceLayer->getSourceInstance(),
            'layerId' => $instanceLayer->getId(),
        ));
    }

    /**
     * @param WmsInstanceLayer $instanceLayer
     * @return array
     */
    protected function getTreeOptionsLayerConfig(WmsInstanceLayer $instanceLayer)
    {
        $hasChildren = !!count($instanceLayer->getSublayer());
        return array(
            "info" => $instanceLayer->getInfo(),
            "selected" => $instanceLayer->getSelected(),
            "toggle" => $hasChildren ? $instanceLayer->getToggle() : null,
            "allow" => array(
                "info" => $instanceLayer->getAllowinfo(),
                "selected" => $instanceLayer->getAllowselected(),
                "toggle" => $hasChildren ? $instanceLayer->getAllowtoggle() : null,
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
        $rootLayer = $sourceInstance->getRootlayer();
        return $this->getLayerBboxConfiguration($rootLayer->getSourceItem());
    }

    /**
     * @param WmsLayerSource $layer
     * @return float[][]
     */
    protected function getLayerBboxConfiguration(WmsLayerSource $layer)
    {
        $configs = array();
        $bbox = $layer->getLatlonBounds();
        if ($bbox) {
            $configs[$bbox->getSrs()] = $bbox->toCoordsArray();
        }
        return $configs;
    }

    protected function getAvailableStyles(WmsLayerSource $layer)
    {
        return $layer->getStyles(true);
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
     * @param WmsInstanceLayer $instanceLayer
     * @return array
     */
    public function getLegendConfig(WmsInstanceLayer $instanceLayer)
    {
        $legendUrl = $this->getInternalLegendUrl($instanceLayer);

        // HACK for reusable source instances: suppress / skip url generation if instance is not owned by a Layerset
        // @todo: implement legend url generation for reusable instances
        if ($legendUrl) {
            if ($this->useTunnel($instanceLayer->getSourceInstance())) {
                // request via tunnel, see ApplicationController::instanceTunnelLegendAction
                $tunnelService = $this->urlProcessor->getTunnelService();
                $publicLegendUrl = $tunnelService->generatePublicLegendUrl($instanceLayer);
            } else {
                $publicLegendUrl = $legendUrl;
            }
            return array(
                "url" => $publicLegendUrl,
            );
        }
        return array();
    }

    public function getInternalLegendUrl(SourceInstanceItem $instanceLayer): ?string
    {
        /** @var WmsInstanceLayer $instanceLayer */
        // scan styles for legend url entries backwards
        // some WMS services may not populate every style with a legend, so just checking the last
        // style for a legend is not enough
        // @todo: style node selection should follow configured style
        $layerSource = $instanceLayer->getSourceItem();
        foreach (array_reverse($layerSource->getStyles(false)) as $style) {
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
     *
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

    public function getScriptAssets(Application $application): array
    {
        return array(
            '@MapbenderCoreBundle/Resources/public/mapbender.geosource.js',
            '@MapbenderWmsBundle/Resources/public/mapbender.geosource.wms.js',
        );
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
}
