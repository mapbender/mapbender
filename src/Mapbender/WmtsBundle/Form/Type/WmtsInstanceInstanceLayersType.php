<?php

namespace Mapbender\WmtsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class WmtsInstanceInstanceLayersType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text', array(
                'required' => true,
                'label' => 'mb.wms.wmsloader.repo.instance.label.title',
            ))
            ->add('basesource', 'checkbox', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.basesource',
            ))
            ->add('proxy', 'checkbox', array(
                'required' => false,
                'label' => 'mb.wms.wmsloader.repo.instance.label.proxy',
            ))
            ->add('opacity', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'attr' => array(
                    'min' => 0,
                    'max' => 100,
                    'step' => 10,
                ),
                'constraints' => array(
                    new Constraints\Range(array(
                        'min' => 0,
                        'max' => 100,
                    )),
                ),
                'required' => true,
            ))
            ->add('layers', 'Symfony\Component\Form\Extension\Core\Type\CollectionType', array(
                'entry_type' => 'Mapbender\WmtsBundle\Form\Type\WmtsInstanceLayerType',
                'options' => array(
                    'data_class' => 'Mapbender\WmtsBundle\Entity\WmtsInstanceLayer',
                ),
            ))
            ->add('roottitle', 'text', array(
                'required' => true,
                'label' => 'mb.wms.wmsloader.repo.instancelayerform.label.layerstitle',
            ))
            ->add('active', 'checkbox', array(
                'required' => false,
                'mapped' => false,
            ))
            ->add('selected', 'checkbox', array(
                'required' => false,
                'mapped' => false,
            ))
            ->add('info', 'checkbox', array(
                'required' => false,
                'disabled' => true,
                'mapped' => false,
            ))
            ->add('allowselected', 'checkbox', array(
                'required' => false,
                'mapped' => false,
            ))
            ->add('allowinfo', 'checkbox', array(
                'required' => false,
                'disabled' => true,
                'mapped' => false,
            ))
        ;
    }
}
