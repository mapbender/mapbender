<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Source\Tunnel\Endpoint;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\WmsBundle\Component\Dimension;

/**
 * Description of SourceInstanceEntityHandler
 *
 * @author Paul Schmidt
 *
 * @property SourceInstance $entity
 */
abstract class SourceInstanceEntityHandler extends EntityHandler
{
    /** @var InstanceTunnelService */
    protected $tunnel;

    /**
     * @param array $configuration
     * @return SourceInstance
     * @internal param SourceInstance $instance
     */
    abstract public function setParameters(array $configuration = array());

    /**
     * Copies attributes from bound instance's source to the bound instance
     * @deprecated
     * If the source is already bound to the instance....
     */
    abstract public function create();
    
    /**
     * Update instance parameters
     */
    abstract public function update();
    
    /**
     * Returns the instance configuration with signed urls or null if an instance configuration isn't valid.
     * @return array instance configuration or null
     */
    abstract public function getConfiguration(Signer $signer);
    
    /**
     * Generates an instance configuration
     */
    abstract public function generateConfiguration();
    
    /**
     * Merges a fiving dimension with an existing.
     * @param Dimension $dimension a diemsion
     */
    abstract public function mergeDimension($dimension);
    
    /**
     * Returns an array with sensitive vendor specific parameters
     */
    abstract public function getSensitiveVendorSpecific();

    /**
     * @return Endpoint
     */
    protected function getTunnel()
    {
        if (!$this->tunnel) {
            /** @var InstanceTunnelService $tunnelService */
            $tunnelService = $this->container->get('mapbender.source.instancetunnel.service');
            $this->tunnel = $tunnelService->makeEndpoint($this->entity);
        }
        return $this->tunnel;
    }
}
