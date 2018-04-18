<?php

namespace Mapbender\CoreBundle\Component\Presenter;

use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\Source\Tunnel\Endpoint;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\VendorSpecific;
use Mapbender\WmsBundle\Component\VendorSpecificHandler;
use Mapbender\WmsBundle\Component\WmsInstanceLayerEntityHandler;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generator for frontend-facing configuration for SourceInstance entities.
 * Plugged into Application\ConfigService as the default generator.
 * May only support WmsInstance entities.
 *
 * Instance registered in container as mapbender.presenter.source.service, see services.xml
 */
class SourceService
{
    /** @var ContainerInterface */
    protected $container;
    /** @var Signer */
    protected $signer;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->signer = $container->get('signer');
    }

    /**
     * @param SourceInstance $sourceInstance
     * @return mixed[]
     */
    public function getConfiguration(SourceInstance $sourceInstance)
    {
        // @todo: make this awesome
        $handler = SourceInstanceEntityHandler::createHandler($this->container, $sourceInstance);
        $innerConfig = $handler->getConfiguration($this->signer);
        $wrappedConfig = array(
            'type'          => strtolower($sourceInstance->getType()),
            'title'         => $sourceInstance->getTitle(),
            'configuration' => $innerConfig,
        );
        return $wrappedConfig;
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
     * After generating a configuration array, this method can make decisions about URL obfuscation etc.
     * E.g. this implementation changes public urls to use the instance tunnel if they contain sensitive parameters.
     *
     * @param WmsInstance $sourceInstance
     * @param mixed[] $configuration
     * @return mixed[]
     */
    public function postProcess(WmsInstance $sourceInstance, $configuration)
    {
        $hide = false;
        $params = array();
        foreach ($sourceInstance->getVendorspecifics() as $key => $vendorspec) {
            $handler = new VendorSpecificHandler($vendorspec);
            if ($handler->isVendorSpecificValueValid()) {
                if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE ||
                    ($vendorspec->getVstype() !== VendorSpecific::TYPE_VS_SIMPLE && !$vendorspec->getHidden())) {
                    $user = $this->container->get('security.context')->getToken()->getUser();
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
                $this->proxifyUrls($configuration['children'][0]);
            }
        }
        $status = $sourceInstance->getSource()->getStatus();
        $configuration['status'] = $status && $status === Source::STATUS_UNREACHABLE ? 'error' : 'ok';
        return $configuration;
    }

    /**
     * Extend URLs in already generated configuration with an owsproxy-compatible signature
     * @todo: this should and can be part of the initial generation
     *
     * @param $layerConfig
     */
    protected function proxifyUrls($layerConfig)
    {
        if (isset($layerConfig['options']['legend'])) {
            if (isset($layerConfig['options']['legend']['graphic'])) {
                $layerConfig['options']['legend']['graphic'] = $this->signer->signUrl($layerConfig['options']['legend']['graphic']);
            } elseif (isset($layer['options']['legend']['url'])) {
                $layerConfig['options']['legend']['url'] = $this->signer->signUrl($layerConfig['options']['legend']['url']);
            }
        }
        if (isset($layerConfig['children'])) {
            foreach ($layerConfig['children'] as &$child) {
                $this->proxifyUrls($child);
            }
        }
    }

    /**
     * @param SourceInstance $sourceInstance
     * @return Endpoint
     */
    public function makeTunnelEndpoint(SourceInstance $sourceInstance)
    {
        /** @var InstanceTunnelService $tunnelService */
        $tunnelService = $this->container->get('mapbender.source.instancetunnel.service');
        return $tunnelService->makeEndpoint($sourceInstance);
    }
}
