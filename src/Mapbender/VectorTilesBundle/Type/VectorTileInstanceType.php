<?php

namespace Mapbender\VectorTilesBundle\Type;

use Mapbender\ManagerBundle\Form\Type\SourceInstanceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class VectorTileInstanceType extends AbstractType
{
    public function getParent()
    {
        return SourceInstanceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('minZoom', IntegerType::class, [
                'required' => false,
                'label' => 'mb.vectortiles.admin.min_zoom',
            ])
            ->add('maxZoom', IntegerType::class, [
                'required' => false,
                'label' => 'mb.vectortiles.admin.max_zoom',
            ])
        ;
    }
}
