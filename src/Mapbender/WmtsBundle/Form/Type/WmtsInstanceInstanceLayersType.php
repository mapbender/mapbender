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
            ->add('layers', 'Mapbender\ManagerBundle\Form\Type\SourceInstanceLayerCollectionType', array(
                'entry_type' => 'Mapbender\WmtsBundle\Form\Type\WmtsInstanceLayerType',
                'options' => array(
                    'data_class' => 'Mapbender\WmtsBundle\Entity\WmtsInstanceLayer',
                ),
            ))
            ->add('roottitle', 'text', array(
                'required' => true,
                'label' => 'mb.wms.wmsloader.repo.instancelayerform.label.layerstitle',
            ))
        ;
    }
}
