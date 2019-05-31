<?php

namespace Mapbender\WmtsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mapbender\WmtsBundle\Form\EventListener\FieldSubscriber;

/**
 * WmtsInstanceLayerType
 * @author Paul Schmidt
 */
class WmtsInstanceLayerType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmtsinstancelayer';
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
                'required' => false))
            ->add('active', 'checkbox', array(
                'required' => false))
            ->add('selected', 'checkbox', array(
                'required' => false))
            ->add('info', 'checkbox', array(
                'required' => false,
                'disabled' => true))
            ->add('toggle', 'checkbox', array(
                'required' => false,
                'disabled' => true))
            ->add('allowselected', 'checkbox', array(
                'required' => false))
            ->add('allowinfo', 'checkbox', array(
                'required' => false,
                'disabled' => true))
            ->add('allowtoggle', 'checkbox', array(
                'required' => false,
                'disabled' => true))
            ->add('style', 'choice', array(
                'label' => 'style',
                'choices' => array(),
                'required' => false))
            ->add('infoformat', 'choice', array(
                'label' => 'style',
                'choices' => array(),
                'required' => false,
            ))
        ;
    }
}
