<?php

namespace Mapbender\WmsBundle\Form\Type;

//use Mapbender\CoreBundle\Form\DataTransformer\ObjectArrayTransformer;
use Mapbender\WmsBundle\Form\DataTransformer\DimensionTransformer;
use Mapbender\WmsBundle\Form\EventListener\DimensionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * DimensionInstType class
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
        $transformer = new DimensionTransformer();#'Mapbender\WmsBundle\Component\DimensionInst');
        $builder->addModelTransformer($transformer);
        $builder->add('active', 'checkbox', array('required' => true, ));
    }

}
