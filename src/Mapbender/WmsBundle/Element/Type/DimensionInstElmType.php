<?php

namespace Mapbender\WmsBundle\Element\Type;

//use Mapbender\CoreBundle\Form\DataTransformer\ObjectArrayTransformer;
//use Mapbender\WmsBundle\Form\DataTransformer\DimensionTransformer;
//use Mapbender\WmsBundle\Form\EventListener\DimensionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * DimensionInstType class
 */
class DimensionInstElmType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return "dimensioninstelm";
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
//            'data_class' => 'Mapbender\WmsBundle\Component\DimensionInst'
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('extent', 'hidden',
                      array('attr' => array('data-extent' => 'group-dimension-extent', 'data-name' => 'extent')))
            ->add('name', 'hidden', array('attr' => array('data-name' => 'name')))
            ->add('units', 'hidden', array('attr' => array('data-name' => 'units')))
            ->add('unitSymbol', 'hidden', array('attr' => array('data-name' => 'unitSymbol')))
            ->add('default', 'hidden', array('attr' => array('data-name' => 'default')))
            ->add('multipleValues', 'hidden', array('attr' => array('data-name' => 'multipleValues')))
            ->add('nearestValue', 'hidden', array('attr' => array('data-name' => 'nearestValue')))
            ->add('current', 'hidden', array('attr' => array('data-name' => 'current')))
            ->add('type', 'hidden', array('attr' => array('data-name' => 'type')))
            ->add('active', 'hidden', array('attr' => array('data-name' => 'active')))
            ->add('origextent', 'hidden', array('attr' => array('data-name' => 'origextent')))
        ;
    }

}
