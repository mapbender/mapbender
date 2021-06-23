<?php

namespace Mapbender\WmsBundle\Form\Type;

use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DimensionInstType extends AbstractType implements EventSubscriberInterface
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array('instance'));
        $resolver->setAllowedTypes('instance', array('Mapbender\WmsBundle\Entity\WmsInstance'));
        $resolver->setDefault('data_class', 'Mapbender\WmsBundle\Component\DimensionInst');
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this);
        $builder
            ->add('active', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => true,
                'label' => 'active',
            ))
            ->add('name', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'auto_initialize' => false,
                'required' => true,
                'attr' => array(
                    'readonly' => 'readonly',
                ),
            ))
            ->add('units', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'auto_initialize' => false,
                'required' => false,
                'attr' => array(
                    'readonly' => 'readonly',
                ),
            ))
            ->add('unitSymbol', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'auto_initialize' => false,
                'required' => false,
                'attr' => array(
                    'readonly' => 'readonly',
                ),
            ))
            ->add('multipleValues', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'auto_initialize' => false,
                'label' => 'multiple',
                'disabled' => true,
                'required' => false,
            ))
            ->add('nearestValue', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'auto_initialize' => false,
                'label' => 'nearest',
                'disabled' => true,
                'required' => false,
            ))
            ->add('current', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'auto_initialize' => false,
                'label' => 'current',
                'disabled' => true,
                'required' => false,
            ))
        ;
    }

    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData');
    }

    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }
        $this->addFields($form, $data);
    }

    /**
     * @param FormInterface $form
     * @param DimensionInst $data
     */
    protected function addFields($form, $data)
    {
        $instance = $form->getConfig()->getOption('instance');
        $originalExtent = $this->getOriginalExtent($instance, $data->getName());
        $ranges = explode(',', $originalExtent);

        $multipleRanges = count($ranges) > 1;
        if ($multipleRanges) {
            $extentType = 'Symfony\Component\Form\Extension\Core\Type\HiddenType';
        } else {
            $extentType = 'Symfony\Component\Form\Extension\Core\Type\TextType';
        }
        $form
            ->add('extent', $extentType, array(
                'required' => true,
                'attr' => array(
                    'readonly' => 'readonly',
                ),
                'label' => 'Extent',
            ))
        ;
        if ($multipleRanges) {
            $choices = array_combine($ranges, $ranges);
            $form->add('extentRanges', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'data' => explode(',', $data->getExtent()),
                'mapped' => false,
                'choices' => $choices,
                'label' => $form->get('extent')->getConfig()->getOption('label'),
                'auto_initialize' => false,
                'multiple' => true,
                'required' => true,
            ));
        }

        $form->add('default', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
            'required' => false,
            'attr' => array(
                'readonly' => 'readonly',
            ),
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var DimensionInst $dimInst */
        $dimInst = $form->getData();
        $view->vars['diminstconfig'] = $dimInst->getConfiguration();
        /** @var WmsInstance $instance */
        $instance = $options['instance'];
        $view->vars['origextent'] = $this->getOriginalExtent($instance, $dimInst->getName());
    }

    protected function getOriginalExtent(WmsInstance $instance, $dimensionName)
    {
        foreach ($instance->getSource()->getDimensions() as $sourceDimension) {
            if ($sourceDimension->getName() === $dimensionName) {
                return $sourceDimension->getExtent();
            }
        }
        return null;
    }
}
