<?php


namespace Mapbender\CoreBundle\Element\Type\Subscriber;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\ManagerBundle\Form\Type\SortableCollectionType;
use Mapbender\Utils\ApplicationUtil;
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
        $application = $event->getForm()->getParent()->getParent()->getData()->getApplication();
        if ($application) {
            $sourceInstanceIds = $this->getSourceInstanceIds($application);
            $event->getForm()->getParent()
                ->add('instancesets', SortableCollectionType::class, array(
                    'entry_type' => 'Mapbender\CoreBundle\Element\Type\InstanceSetAdminType',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_options' => array(
                        'application' => $application,
                        'choice_filter' => function($choice) use ($sourceInstanceIds) {
                            /** @var SourceInstance $choice*/
                            return \in_array($choice->getId(), $sourceInstanceIds, false);
                        },
                    ),
                ))
            ;
        }
    }

    /**
     * @param Application $application
     * @return array
     */
    protected function getSourceInstanceIds(Application $application)
    {
        $sourceInstanceIds = array();
        foreach (ApplicationUtil::getMapLayersets($application) as $layerset) {
            foreach ($layerset->getCombinedInstanceAssignments() as $assignment) {
                if ($assignment->getEnabled() && $assignment->getInstance()->isBasesource()) {
                    $sourceInstanceIds[] = $assignment->getInstance()->getId();
                }
            }
        }
        return $sourceInstanceIds;
    }
}
