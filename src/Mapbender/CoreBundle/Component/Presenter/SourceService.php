<?php

namespace Mapbender\CoreBundle\Component\Presenter;

use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Entity\SourceInstance;
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
}
