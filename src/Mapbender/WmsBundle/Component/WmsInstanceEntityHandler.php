<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Entity\WmsInstance;

/**
 * @author Paul Schmidt
 *
 * @property WmsInstance $entity
 */
class WmsInstanceEntityHandler extends SourceInstanceEntityHandler
{
    /**
     * Populates bound instance with array values. Used exclusively by
     * ApplicationYAMLMapper ..?
     *
     * @param array $configuration
     * @return WmsInstance
     */
    public function setParameters(array $configuration = array())
    {
        /** @var TypeDirectoryService $typeDirectory */
        $typeDirectory = $this->container->get('mapbender.source.typedirectory.service');
        $instanceFactory = $typeDirectory->getInstanceFactoryByType(Source::TYPE_WMS);
        /** @var WmsInstance $instance */
        $instance = $instanceFactory->fromConfig($configuration, $this->entity->getId());
        $this->entity
            ->setSource($instance->getSource())
            ->setLayers($instance->getLayers())
            ->setProxy($instance->getProxy())
            ->setVisible($instance->getVisible())
            ->setFormat($instance->getFormat())
            ->setInfoformat($instance->getInfoformat())
            ->setTransparency($instance->getTransparency())
            ->setOpacity($instance->getOpacity())
            ->setTitle($instance->getTiled())
            ->setBasesource($instance->isBasesource())
        ;
        if ($instance->getLayerOrder()) {
            $this->entity->setLayerOrder($instance->getLayerOrder());
        }
        return $this->entity;
    }
}
