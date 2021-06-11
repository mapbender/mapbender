<?php

namespace Mapbender\WmsBundle\Element\Type\Subscriber;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DimensionsHandlerMapTargetSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData');
    }

    public function preSetData(FormEvent $event)
    {
        $mapId = $event->getData();
        /** @var Element $element */
        $element = $event->getForm()->getParent()->getParent()->getData();
        $application = $element->getApplication();
        if ($mapId && $application) {
            $dimensions = $this->collectDimensions($application, $mapId);
        } else {
            $dimensions = array();
        }
        $event->getForm()->getParent()
            ->add('dimensionsets', "Symfony\Component\Form\Extension\Core\Type\CollectionType", array(
                'entry_type' => 'Mapbender\WmsBundle\Element\Type\DimensionSetAdminType',
                'allow_add' => !!count($dimensions),
                'allow_delete' => true,
                'auto_initialize' => false,
                'entry_options' => array(
                    'dimensions' => $dimensions,
                ),
            ))
        ;
    }

    /**
     * @param Application $application
     * @param int $mapId
     * @return DimensionInst[]
     */
    protected function collectDimensions(Application $application, $mapId)
    {
        $dimensions = array();
        foreach ($this->getMapLayersets($application, $mapId) as $layerset) {
            foreach ($layerset->getInstances(true) as $instance) {
                if ($instance->getEnabled() && ($instance instanceof WmsInstance)) {
                    foreach ($instance->getDimensions() ?: array() as $ix => $dimension) {
                        /** @var DimensionInst $dimension */
                        $key = "{$instance->getId()}-{$ix}";
                        $dimension->id = $key;
                        $dimensions[$key] = $dimension;
                    }
                }
            }
        }
        return $dimensions;
    }

    /**
     * @param Application $application
     * @param int|string $elementId
     * @return mixed[]
     */
    protected function getElementConfiguration($application, $elementId)
    {
        foreach ($application->getElements() as $element) {
            if (strval($element->getId()) === strval($elementId)) {
                return $element->getConfiguration();
            }
        }
        throw new \RuntimeException("No Element with id " . var_export($elementId, true));
    }

    /**
     * @param Application $application
     * @param int|string $mapId
     * @return Layerset[]
     */
    protected function getMapLayersets($application, $mapId)
    {
        $mapConfig = $this->getElementConfiguration($application, $mapId);
        $layersetIds = array_map('strval', ArrayUtil::getDefault($mapConfig, 'layersets', array()));
        $layersets = array();
        foreach ($application->getLayersets() as $layerset) {
            if (in_array(strval($layerset->getId()), $layersetIds)) {
                $layersets[] = $layerset;
            }
        }
        return $layersets;
    }
}
