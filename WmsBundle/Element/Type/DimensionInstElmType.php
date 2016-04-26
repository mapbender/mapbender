<?php

namespace Mapbender\WmsBundle\Element\Type;

use Mapbender\WmsBundle\Element\DataTransformer\DimensionTransformer;
use Mapbender\WmsBundle\Element\EventListener\DimensionSubscriber;
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
        $resolver->setDefaults(array());
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subscriber = new DimensionSubscriber($builder->getFormFactory());
        $builder->addEventSubscriber($subscriber);
        $transformer = new DimensionTransformer();
        $builder->addModelTransformer($transformer);
    }

}
