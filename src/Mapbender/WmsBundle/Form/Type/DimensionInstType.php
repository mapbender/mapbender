<?php

namespace Mapbender\WmsBundle\Form\Type;

use Mapbender\WmsBundle\Form\DataTransformer\DimensionTransformer;
use Mapbender\WmsBundle\Form\EventListener\DimensionSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array());
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subscriber = new DimensionSubscriber();
        $builder->addEventSubscriber($subscriber);
        $transformer = new DimensionTransformer();
        $builder->addModelTransformer($transformer);
        $builder->add('active', 'checkbox', array('required' => true, ));
    }

}
