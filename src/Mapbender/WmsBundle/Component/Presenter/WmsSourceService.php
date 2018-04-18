<?php


namespace Mapbender\WmsBundle\Component\Presenter;

use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\VendorSpecific;
use Mapbender\WmsBundle\Component\VendorSpecificHandler;
use Mapbender\WmsBundle\Component\WmsInstanceConfigurationOptions;
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
            'options' => WmsInstanceConfigurationOptions::fromEntity($sourceInstance)->toArray(),
            'children' => array($this->getRootLayerConfig($sourceInstance)),
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
}

