<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * DimensionIntervalType class
 */
class DimensionIntervalType extends AbstractType
{

    public function getName()
    {
        return 'dimension';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'origExtent' => null,
                'start' => null,
                'end' => null,
                'interval' => null,
                'extent' => null,
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'hidden', array('required' => true))
            ->add('units', 'hidden', array('required' => false))
            ->add('unitSymbol', 'hidden', array('required' => false))
            ->add('default', 'hidden', array('required' => false))
            ->add('multipleValues', 'hidden', array('required' => false))
            ->add('nearestValue', 'hidden', array('required' => false))
            ->add('current', 'hidden', array('required' => false))
            ->add('extent', 'hidden', array(
                'required' => true))
            ->add('start', 'hidden', array(
                'required' => true,
                'mapped' => false))
            ->add('end', 'hidden', array(
                'required' => true,
                'mapped' => false))
            ->add('interval', 'hidden', array(
                'required' => true,
                'mapped' => false))
            ->add('origExtent', 'hidden', array(
                'required' => true,
                'mapped' => false));
    }

}
