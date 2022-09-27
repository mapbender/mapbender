<?php


namespace Mapbender\WmtsBundle\Form\Type;


use Mapbender\ManagerBundle\Form\Type\SourceInstanceLayerCollectionType;
use Mapbender\ManagerBundle\Form\Type\SourceInstanceType;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WmtsInstanceType extends AbstractType
{
    public function getParent()
    {
        return SourceInstanceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('layers', SourceInstanceLayerCollectionType::class, array(
                'entry_type' => WmtsInstanceLayerType::class,
                'entry_options' => array(
                    'data_class' => WmtsInstanceLayer::class,
                ),
            ))
        ;
    }
}
