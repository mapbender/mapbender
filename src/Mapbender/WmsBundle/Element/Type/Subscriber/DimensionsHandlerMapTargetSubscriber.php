<?php

namespace Mapbender\WmsBundle\Element\Type\Subscriber;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DimensionsHandlerMapTargetSubscriber implements EventSubscriberInterface
{
    /** @var Application */
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData');
    }

    public function preSetData(FormEvent $event)
    {
        $mapId = $event->getForm()->get('target')->getData();
        if ($mapId && $dimensions = $this->collectDimensions($this->application, $mapId)) {
            $event->getForm()
                ->add('dimensionsets', "Symfony\Component\Form\Extension\Core\Type\CollectionType", array(
                    'entry_type' => 'Mapbender\WmsBundle\Element\Type\DimensionSetAdminType',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'auto_initialize' => false,
                    'options' => array(
                        'dimensions' => $dimensions,
                    ),
                ))
            ;
        }
    }

    /**
     * @param Application $application
     * @param int $mapId
     * @return DimensionInst[]
     */
    protected function collectDimensions($application, $mapId)
    {
        $dimensions = array();
        foreach ($this->getMapLayersets($application, $mapId) as $layerset) {
            foreach ($layerset->getInstances() as $instance) {
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
