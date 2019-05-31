<?php

namespace Mapbender\WmtsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * WmisInstanceInstanceLayersType class
 */
class WmtsInstanceInstanceLayersType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmtsinstanceinstancelayers';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $opacity = array();
        foreach (range(0, 100, 10) as $value) {
            $opacity[$value] = $value;
        }
        $builder
            ->add('title', 'text', array(
                'required' => true))
            ->add('basesource', 'checkbox', array(
                'required' => false))
            ->add('visible', 'checkbox', array(
                'required' => false))
            ->add('proxy', 'checkbox', array(
                'required' => false))
            ->add('opacity', 'choice', array(
                'choices' => $opacity,
                'required' => true))
            ->add('layers', 'collection', array(
                'type' => new WmtsInstanceLayerType(),
                'options' => array(
                    'data_class' => 'Mapbender\WmtsBundle\Entity\WmtsInstanceLayer',
                ),
            ))
            ->add('roottitle', 'text', array(
                'required' => true))
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
        ;
    }
}
