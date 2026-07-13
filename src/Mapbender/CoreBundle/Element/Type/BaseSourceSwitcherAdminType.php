<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\ManagerBundle\Form\Type\SortableCollectionType;
use Mapbender\Utils\ApplicationUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class BaseSourceSwitcherAdminType extends AbstractType implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tooltip', TextType::class, [
                'required' => false,
                'label' => 'mb.core.basesourceswitcher.admin.tooltip',
                ]);
        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents(): array
    {
        return [FormEvents::PRE_SET_DATA => 'preSetData'];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var Application $application */
        $application = $event->getForm()->getParent()->getData()->getApplication();
        if ($application) {
            $sourceInstanceIds = $this->getSourceInstanceIds($application);
            $event->getForm()
                ->add('instancesets', SortableCollectionType::class, [
                    'entry_type' => InstanceSetAdminType::class,
                    'label' => 'mb.core.basesourceswitcher.admin.instancesets',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_options' => [
                        'application' => $application,
                        'choice_filter' => function($choice) use ($sourceInstanceIds): bool {
                            /** @var SourceInstance|int $choice*/
                            $choiceId = $choice instanceof SourceInstance ? $choice->getId() : $choice;
                            return \in_array($choiceId, $sourceInstanceIds, false);
                        },
                    ],
                ])
            ;
        }
    }

    /**
     * @param Application $application
     * @return array
     */
    protected function getSourceInstanceIds(Application $application): array
    {
        $sourceInstanceIds = [];
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
