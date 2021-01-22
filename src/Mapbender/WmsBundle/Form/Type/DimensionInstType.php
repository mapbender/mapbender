<?php

namespace Mapbender\WmsBundle\Form\Type;

use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Form\DataTransformer\DimensionTransformer;
use Mapbender\WmsBundle\Form\EventListener\DimensionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class DimensionInstType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subscriber = new DimensionSubscriber();
        $builder->addEventSubscriber($subscriber);
        $transformer = new DimensionTransformer();
        $builder->addModelTransformer($transformer);
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

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var DimensionInst $dimInst */
        $dimInst = $form->getData();
        $view->vars['diminstconfig'] = array_replace($dimInst->getConfiguration(), array(
            'origextent' => $dimInst->getOrigextent(),
        ));
    }
}
