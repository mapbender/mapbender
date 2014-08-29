<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * DimensionMultipleType class
 */
class DimensionMultipleType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return "dimension";
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'origExtent' => array()
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'hidden', array('required' => true))
            ->add('units', 'hidden', array('required' => false))
            ->add('unitSymbol', 'hidden', array('required' => false))
            ->add('default', 'hidden', array('required' => false))
            ->add('multipleValues', 'hidden', array('required' => false))
            ->add('nearestValue', 'hidden', array('required' => false))
            ->add('current', 'hidden', array('required' => false))
            ->add('extent', 'choice',
                array(
                'choices' => $options['origExtent'],
                'required' => true,
                'multiple' => true,
            ))
        ;
    }

}
