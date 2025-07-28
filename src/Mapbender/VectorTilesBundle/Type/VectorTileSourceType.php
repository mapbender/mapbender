<?php

namespace Mapbender\VectorTilesBundle\Type;

use Mapbender\ManagerBundle\Form\Type\SourceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class VectorTileSourceType extends SourceType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jsonUrl', UrlType::class, [
                'label' => 'mb.vectortiles.admin.json_url',
            ])
            ->add('referer', TextType::class, [
                'label' => 'mb.vectortiles.admin.referer',
                'required' => false,
                'help' => 'mb.vectortiles.admin.referer.help',
            ])
        ;
    }

}
