<?php

namespace Mapbender\VectorTilesBundle\Type;

use Mapbender\ManagerBundle\Form\Type\SourceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class VectorTileSourceType extends SourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jsonUrl', UrlType::class, array(
                    'label' => 'mb.vectortiles.admin.json_url',
                )
            )
        ;
    }

}
