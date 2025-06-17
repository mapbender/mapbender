<?php

namespace Mapbender\VectorTilesBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class VectorTileSourceType extends AbstractType
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
