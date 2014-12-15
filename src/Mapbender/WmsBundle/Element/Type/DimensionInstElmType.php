<?php

namespace Mapbender\WmsBundle\Element\Type;

//use Mapbender\CoreBundle\Form\DataTransformer\ObjectArrayTransformer;
use Mapbender\WmsBundle\Form\DataTransformer\DimensionTransformer;
use Mapbender\WmsBundle\Form\EventListener\DimensionSubscriber;
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
        $builder->add('extent', 'hidden', array('attr' => array('data-extent' => 'group-dimension-extent')))
            ->add('name', 'hidden')
            ->add('units', 'hidden')
            ->add('unitSymbol', 'hidden')
            ->add('default', 'hidden')
            ->add('multipleValues', 'hidden')
            ->add('nearestValue', 'hidden')
            ->add('current', 'hidden')
            ->add('type', 'hidden')
            ->add('active', 'hidden')
            ->add('origextent', 'hidden')
            ;
    }

}
