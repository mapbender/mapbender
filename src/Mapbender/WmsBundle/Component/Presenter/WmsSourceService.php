<?php


namespace Mapbender\WmsBundle\Component\Presenter;

use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\VendorSpecificHandler;
use Mapbender\WmsBundle\Component\WmsInstanceLayerEntityHandler;
use Mapbender\WmsBundle\Entity\WmsInstance;

/**
 * Instance registered in container at mapbender.source.wms.service and aliased as
 * mapbender.source.default.service (because it's the default and the only one we start with),
 * see services.xml
 */
class WmsSourceService extends SourceService
{

    public function getInnerConfiguration(SourceInstance $sourceInstance)
    {
        /** @var WmsInstance $sourceInstance */
        return parent::getInnerConfiguration($sourceInstance) + array(
            'options' => $this->getOptionsConfiguration($sourceInstance),
            'children' => array($this->getRootLayerConfig($sourceInstance)),
        );
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
            'proxy' => $sourceInstance->getProxy(),
            'visible' => $sourceInstance->getVisible(),
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

    public function postProcessInnerConfiguration(SourceInstance $sourceInstance, $configuration)
    {
        /** @var WmsInstance $sourceInstance */
        $configuration = parent::postProcessInnerConfiguration($sourceInstance, $configuration);
        // upstream may return null if validation fails...
        if ($configuration) {
            $configuration = $this->postProcessUrls($sourceInstance, $configuration);
        }
        return $configuration;
    }

    /**
     * NOTE: only WmsInstances have a root layer. SourceInstance does not define this.
     * @todo: this technically makes this whole class WmsInstance-specific, so it should be renamed / moved
     *
     * @param WmsInstance $sourceInstance
     * @return array
     */
    public function getRootLayerConfig(WmsInstance $sourceInstance)
    {
        $rootlayer = $sourceInstance->getRootlayer();
        $entityHandler = new WmsInstanceLayerEntityHandler($this->container, null);
        $rootLayerConfig = $entityHandler->generateConfiguration($rootlayer);
        return $rootLayerConfig;
    }

    /**
     * @todo: tunnel vs no-tunnel based on "sensitive" VendorSpecifics may not be cachable, investigate
     *
     * @param WmsInstance $sourceInstance
     * @param mixed[] $configuration
     * @return mixed[] modified configuration
     */
    public function postProcessUrls(WmsInstance $sourceInstance, $configuration)
    {
        $vsHandler = new VendorSpecificHandler();
        if ($sourceInstance->getSource()->getUsername() || $vsHandler->hasHiddenParams($sourceInstance)) {
            $url = $this->urlProcessor->getPublicTunnelBaseUrl($sourceInstance);
            $configuration['options']['url'] = $url;
            // remove ows proxy for a tunnel connection
            $configuration['options']['tunnel'] = true;
        } else {
            if ($sourceInstance->getProxy()) {
                $configuration['options']['url'] = $this->urlProcessor->proxifyUrl($configuration['options']['url']);
                $configuration['children'][0] = $this->proxifyLayerUrls($configuration['children'][0]);
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
        $userToken = $this->container->get('security.token_storage')->getToken();
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
        $boundingBoxMap = array();
        foreach ($rootLayer->getSourceItem()->getMergedBoundingBoxes() as $bbox) {
            $boundingBoxMap[$bbox->getSrs()] = $bbox->toCoordsArray();
        }
        return $boundingBoxMap;
    }

    /**
     * Return the collected configuration arrays from all Dimensions on the given $sourceInstance
     *
     * @param WmsInstance $sourceInstance
     * @return array[]
     */
    public function getDimensionsConfiguration(WmsInstance $sourceInstance)
    {
        $dimensions = array();
        foreach ($sourceInstance->getDimensions() as $dimension) {
            if ($dimension->getActive()) {
                $dimensions[] = $dimension->getConfiguration();
            }
        }
        return $dimensions;
    }

    /**
     * @param WmsInstance $sourceInstance
     * @todo: This belongs in the repository layer. TBD if we can access the container / other services there.
     */
    public function initializeInstance(SourceInstance $sourceInstance)
    {
        /** @var WmsInstance $sourceInstance */
        parent::initializeInstance($sourceInstance);
        $this->initializeLayerOrder($sourceInstance);
    }

    /**
     * Initialize layer order if a default is configured. This configuration is optional. It's only applied to
     * NEW WmsInstances. The parameter key for this default value is wms.default_layer_order (can be set in
     * parameters.yml / xml configs).
     *
     * @param WmsInstance $sourceInstance
     */
    public function initializeLayerOrder(WmsInstance $sourceInstance)
    {
        $layerOrderDefaultKey = 'wms.default_layer_order';
        if ($this->container->hasParameter($layerOrderDefaultKey)) {
            $configuredDefaultLayerOrder = $this->container->getParameter($layerOrderDefaultKey);
            $sourceInstance->setLayerOrder($configuredDefaultLayerOrder);
        }
        /**
         * NOTE: the entity has a built-in default, so new instances will work fine even without setting
         *       layer order explicitly
         * @see WmsInstance::getLayerOrder()
         */
    }

    public function getAssets(Application $application, $type)
    {
        switch ($type) {
            case 'js':
                return array(
                    '@MapbenderCoreBundle/Resources/public/mapbender.geosource.js',
                    '@MapbenderWmsBundle/Resources/public/mapbender.geosource.wms.js',
                );
            case 'trans':
                return array(
                    'MapbenderCoreBundle::geosource.json.twig',
                );
            default:
                throw new \InvalidArgumentException("Unsupported type " . print_r($type, true));
        }
    }
}
