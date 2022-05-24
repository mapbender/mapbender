<?php

namespace Mapbender\WmsBundle\Element\Type;

use Mapbender\Component\ClassUtil;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Utils\ArrayUtil;
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tooltip', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
            ))
        ;
        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents()
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
            foreach ($application->getElements() as $otherElement) {
                $otherClassName = $otherElement->getClass();
                if ($otherClassName && ClassUtil::exists($otherClassName) && \is_a($otherClassName, 'Mapbender\Component\Element\MainMapElementInterface', true)) {
                    // Map found
                    $dimensions = $this->collectDimensions($otherElement);
                    break;
                }
            }
        }
        $event->getForm()
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
     * @param Element $map
     * @return DimensionInst[]
     */
    protected function collectDimensions(Element $map)
    {
        $dimensions = array();
        foreach ($this->getMapLayersets($map) as $layerset) {
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
     * @param Element $map
     * @return Layerset[]
     */
    protected function getMapLayersets(Element $map)
    {
        $mapConfig = $map->getConfiguration();
        $layersetIds = array_map('strval', ArrayUtil::getDefault($mapConfig, 'layersets', array()));
        $layersets = array();
        foreach ($map->getApplication()->getLayersets() as $layerset) {
            if (in_array(strval($layerset->getId()), $layersetIds)) {
                $layersets[] = $layerset;
            }
        }
        return $layersets;
    }
}
