<?php


namespace Mapbender\WmtsBundle\Form\Type;


use Mapbender\ManagerBundle\Form\Type\SourceInstanceLayerCollectionType;
use Mapbender\ManagerBundle\Form\Type\SourceInstanceType;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TmsInstanceType extends AbstractType
{
    public function getParent(): string
    {
        return SourceInstanceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('layers', SourceInstanceLayerCollectionType::class, array(
                'entry_type' => TileInstanceLayerType::class,
                'entry_options' => array(
                    'data_class' => WmtsInstanceLayer::class,
                ),
            ))
        ;
    }
}
