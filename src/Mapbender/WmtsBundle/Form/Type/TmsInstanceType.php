<?php


namespace Mapbender\WmtsBundle\Form\Type;


use Mapbender\ManagerBundle\Form\Type\SourceInstanceItemType;
use Mapbender\ManagerBundle\Form\Type\SourceInstanceLayerCollectionType;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TmsInstanceType extends AbstractType
{
    public function getParent()
    {
        return 'Mapbender\ManagerBundle\Form\Type\SourceInstanceType';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('layers', SourceInstanceLayerCollectionType::class, array(
                'entry_type' => SourceInstanceItemType::class,
                'entry_options' => array(
                    'data_class' => WmtsInstanceLayer::class,
                ),
            ))
        ;
    }
}
