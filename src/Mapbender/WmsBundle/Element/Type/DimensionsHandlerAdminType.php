<?php

namespace Mapbender\WmsBundle\Element\Type;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\ManagerBundle\Form\Type\SortableCollectionType;
use Mapbender\Utils\ApplicationUtil;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * @author Paul Schmidt
 */
class DimensionsHandlerAdminType extends AbstractType implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tooltip', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.core.dimensionshandler.admin.tooltip',
            ))
        ;
        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents(): array
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData');
    }

    public function preSetData(FormEvent $event)
    {
        /** @var Element $element */
        $element = $event->getForm()->getParent()->getData();
        $application = $element->getApplication();
        $dimensions = array();
        if ($application) {
            $dimensions = $this->collectDimensions($application);
        }
        $event->getForm()
            ->add('dimensionsets', SortableCollectionType::class, array(
                'entry_type' => 'Mapbender\WmsBundle\Element\Type\DimensionSetAdminType',
                'label' => 'mb.core.dimensionshandler.admin.dimensionsets',
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
     * @return DimensionInst[]
     */
    protected function collectDimensions(Application $application)
    {
        $dimensions = array();
        foreach (ApplicationUtil::getMapLayersets($application) as $layerset) {
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
}
