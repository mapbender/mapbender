<?php


namespace Mapbender\WmsBundle\Component\Presenter;

use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\VendorSpecific;
use Mapbender\WmsBundle\Component\VendorSpecificHandler;
use Mapbender\WmsBundle\Component\WmsInstanceLayerEntityHandler;
use Mapbender\WmsBundle\Entity\WmsInstance;

/**
 * Instance registered in container at mapbender.presenter.source.wms.service and aliased as
 * mapbender.presenter.source.service (because it's the default and the only one we start with),
 * see services.xml
 */
class WmsSourceService extends SourceService
{

    public function getInnerConfiguration(WmsInstance $sourceInstance)
    {
        return parent::getInnerConfiguration($sourceInstance) + array(
            /** @todo: replace WmsInstanceConfigurationOptions stuff with a local implementation */
            'options' => $this->getOptionsConfiguration($sourceInstance),
            'children' => array($this->getRootLayerConfig($sourceInstance)),
        );
    }

    public function getOptionsConfiguration(WmsInstance $sourceInstance)
    {
        // return WmsInstanceConfigurationOptions::fromEntity($sourceInstance)->toArray();
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
            'vendorspecifics' => $this->getVendorSpecificsConfiguration($sourceInstance),
            'dimensions' => $this->getDimensionsConfiguration($sourceInstance),
            'buffer' => $buffer,
            'ratio' => $ratio,
            'layerOrder' => $sourceInstance->getLayerOrder(),
        );
    }

    public function postProcessInnerConfiguration(WmsInstance $sourceInstance, $configuration)
    {
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
        $user = $this->container->get('security.context')->getToken()->getUser();
        $hide = false;
        $params = array();
        foreach ($sourceInstance->getVendorspecifics() as $key => $vendorspec) {
            $handler = new VendorSpecificHandler($vendorspec);
            if ($handler->isVendorSpecificValueValid()) {
                if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE ||
                    ($vendorspec->getVstype() !== VendorSpecific::TYPE_VS_SIMPLE && !$vendorspec->getHidden())) {
                    $params = array_merge($params, $handler->getKvpConfiguration($user));
                } else {
                    $hide = true;
                }
            }
        }
        if ($hide || $sourceInstance->getSource()->getUsername()) {
            $url = $this->makeTunnelEndpoint($sourceInstance)->getPublicBaseUrl();
            $configuration['options']['url'] = UrlUtil::validateUrl($url, $params, array());
            // remove ows proxy for a tunnel connection
            $configuration['options']['tunnel'] = true;
        } elseif ($this->signer) {
            $configuration['options']['url'] = UrlUtil::validateUrl($configuration['options']['url'], $params, array());
            $configuration['options']['url'] = $this->signer->signUrl($configuration['options']['url']);
            if ($sourceInstance->getProxy()) {
                $this->signLayerUrls($configuration['children'][0]);
            }
        }
        return $configuration;
    }

    public function getUrlOption(WmsInstance $sourceInstance)
    {
        $url = $sourceInstance->getSource()->getGetMap()->getHttpGet();
        $url = $this->addDimensionParameters($sourceInstance, $url);
        $url = $this->addVendorSpecifics($sourceInstance, $url);
        return $url;
    }

    /**
     * Extend the given $url with parameters from the Dimension defaults set on the given $sourceInstance
     *
     * @param WmsInstance $sourceInstance
     * @param string $url
     * @return string
     */
    public function addDimensionParameters(WmsInstance $sourceInstance, $url)
    {
        foreach ($sourceInstance->getDimensions() as $dimension) {
            if ($dimension->getActive() && $dimension->getDefault()) {
                $help = array($dimension->getParameterName() => $dimension->getDefault());
                $url = UrlUtil::validateUrl($url, $help, array());
            }
        }
        return $url;
    }

    /**
     * Extend the given $url with vendor specific parameters set on the given $sourceInstance (only "simple" type)
     *
     * @param WmsInstance $sourceInstance
     * @param string $url
     * @return string
     */
    public function addVendorSpecifics(WmsInstance $sourceInstance, $url)
    {
        foreach ($sourceInstance->getVendorspecifics() as $key => $vendorspec) {
            $handler = new VendorSpecificHandler($vendorspec);
            /* add to url only simple vendor specific with valid default value */
            if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE && $handler->isVendorSpecificValueValid()) {
                $help = $handler->getKvpConfiguration(null);
                $url = UrlUtil::validateUrl($url, $help, array());
            }
        }
        return $url;
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

    public function getVendorSpecificsConfiguration(WmsInstance $sourceInstance)
    {
        $vendorSpecific = array();
        foreach ($sourceInstance->getVendorspecifics() as $key => $vendorspec) {
            $handler = new VendorSpecificHandler($vendorspec);
            if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE && $handler->isVendorSpecificValueValid()) {
                $vendorSpecific[] = $handler->getConfiguration();
            }
        }
        return $vendorSpecific;
    }
}

