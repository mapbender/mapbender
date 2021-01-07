<?php


namespace Mapbender\CoreBundle\Element\Type\Subscriber;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class BaseSourceSwitcherMapTargetSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData');
    }

    public function preSetData(FormEvent $event)
    {
        /** @var Application $application */
        $application = $event->getForm()->getParent()->getData()->getApplication();
        $mapId = $event->getForm()->get('target')->getData();
        if ($application && $mapId) {
            $sourceInstanceIds = $this->getSourceInstanceIdsFromMapId($application, $mapId);
            $event->getForm()
                ->add('instancesets', "Symfony\Component\Form\Extension\Core\Type\CollectionType", array(
                    'entry_type' => 'Mapbender\CoreBundle\Element\Type\InstanceSetAdminType',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'auto_initialize' => false,
                    'entry_options' => array(
                        'application' => $application,
                        'choice_filter' => function($choice) use ($sourceInstanceIds) {
                            /** @var SourceInstance $choice*/
                            $inMapSourceInstances = in_array($choice->getId(), $sourceInstanceIds, false);
                            return $inMapSourceInstances && $choice->isBasesource() && $choice->getEnabled();
                        },
                    ),
                ))
            ;
        }
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
     * @param $mapId
     * @return array
     */
    protected function getSourceInstanceIdsFromMapId(Application $application, $mapId)
    {
        $sourceInstanceIds = array();
        foreach ($this->getLayersetsFromMapId($application, $mapId) as $layerset) {
            foreach ($layerset->getCombinedInstances() as $sourceInstance) {
                $sourceInstanceIds[] = $sourceInstance->getId();
            }
        }
        return $sourceInstanceIds;
    }

    /**
     * @param Application $application
     * @param string $mapId
     * @return Layerset[]
     */
    protected function getLayersetsFromMapId(Application $application, $mapId)
    {
        $layersets = array();
        $mapConfig = $this->getElementConfiguration($application, $mapId);
        if ($mapId) {
            $layersetIds = array_map('strval', ArrayUtil::getDefault($mapConfig, 'layersets', array()));
            $layersets = $application->getLayersets()->filter(function($layerset) use ($layersetIds) {
                /** @var Layerset $layerset */
                return \in_array($layerset->getId(), $layersetIds, false);
            });
        }
        return $layersets;
    }
}
