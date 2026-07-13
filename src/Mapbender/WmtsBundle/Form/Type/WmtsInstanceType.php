<?php


namespace Mapbender\WmtsBundle\Form\Type;


use Mapbender\ManagerBundle\Form\Type\SourceInstanceLayerCollectionType;
use Mapbender\ManagerBundle\Form\Type\SourceInstanceType;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WmtsInstanceType extends AbstractType
{
    public function getParent(): string
    {
        return SourceInstanceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('layers', SourceInstanceLayerCollectionType::class, [
                'entry_type' => WmtsInstanceLayerType::class,
                'entry_options' => [
                    'data_class' => WmtsInstanceLayer::class,
                ],
            ])
        ;
    }
}
