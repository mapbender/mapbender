<?php


namespace Mapbender\CoreBundle\Element\Type\Subscriber;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class BaseSourceSwitcherMapTargetSubscriber implements EventSubscriberInterface
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
        if ($mapId && $baseSourceInstances = $this->collectBaseSourceInstanceChoices($this->application, $mapId)) {
            $event->getForm()
                ->add('instancesets', "Symfony\Component\Form\Extension\Core\Type\CollectionType", array(
                    'property_path' => '[instancesets]',
                    'type' => 'Mapbender\CoreBundle\Element\Type\InstanceSetAdminType',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'auto_initialize' => false,
                    'options' => array(
                        'instances' => $baseSourceInstances,
                    )
                ));
            ;
        }
    }

    /**
     * @param Application $application
     * @param string $mapId
     * @return string[]
     */
    protected function collectBaseSourceInstanceChoices($application, $mapId)
    {
        $instances = array();
        foreach ($this->getMapLayersets($application, $mapId) as $layerset) {
            foreach ($layerset->getInstances() as $instance) {
                if ($instance->isBasesource() && $instance->getEnabled()) {
                    $instances[strval($instance->getId())] = $instance->getTitle();
                }
            }
        }
        return $instances;
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
