<?php

namespace Mapbender\WmsBundle\Form\Type;

use Mapbender\CoreBundle\Form\DataTransformer\ObjectArrayTransformer;
use Mapbender\WmsBundle\Form\EventListener\DimensionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * DimensionType class
 */
class DimensionInstType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return "dimension";
    }
//    /**
//     * @inheritdoc
//     */
//    public function getParent()
//    {
//        return "collection";
//    }

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
        $subscriber = new DimensionSubscriber($builder->getFormFactory());
        $builder->addEventSubscriber($subscriber);
        $transformer = new ObjectArrayTransformer('Mapbender\WmsBundle\Component\DimensionInst');
        $builder->addModelTransformer($transformer);
        $builder
            ->add('use', 'checkbox', array('required' => true, ))
            ->add('name', 'hidden', array('required' => true))
            ->add('units', 'hidden', array('required' => false))
            ->add('unitSymbol', 'hidden', array('required' => false))
            ->add('default', 'hidden', array('required' => false))
            ->add('multipleValues', 'hidden', array('required' => false))
            ->add('nearestValue', 'hidden', array('required' => false))
            ->add('current', 'hidden', array('required' => false))
//            ->add('extent', 'text', array('required' => true))
        ;
    }

}
