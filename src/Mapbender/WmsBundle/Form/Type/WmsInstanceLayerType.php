<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mapbender\WmsBundle\Form\EventListener\FieldSubscriber;

/**
 * 
 */
class WmsInstanceLayerType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmsinstancelayer';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subscriber = new FieldSubscriber($builder->getFormFactory());
        $builder->addEventSubscriber($subscriber);
        $builder
            ->add('title', 'text', array(
                    'required' => false,
            ))
            ->add('active', 'checkbox', array(
                'required' => false,
            ))
            ->add('selected', 'checkbox',
                  array(
                'required' => false))
            ->add('info', 'checkbox',
                  array(
                'required' => false,
                'disabled' => true))
            ->add('toggle', 'checkbox', array(
                'required' => false,
            ))
            ->add('allowselected', 'checkbox', array(
                'required' => false,
            ))
            ->add('allowinfo', 'checkbox', array(
                'required' => false,
                'disabled' => true,
            ))
            ->add('allowtoggle', 'checkbox', array(
                'required' => false,
            ))
            ->add('allowreorder', 'checkbox', array(
                'required' => false,
            ))
            ->add('minScale', 'text',
                  array(
                'required' => false))
            ->add('maxScale', 'text', array(
                'required' => false,
            ))
            ->add('style', 'choice', array(
                'label' => 'style',
                'choices' => array(),
                'required' => false,
            ))
            ->add('priority', 'hidden', array(
                'required' => true,
            ))
        ;
    }

}
