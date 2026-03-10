<?php

namespace Mapbender\OgcApiFeaturesBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Mapbender\ManagerBundle\Form\Type\SourceType;

class OgcApiFeaturesSourceType extends SourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jsonUrl', UrlType::class, [
                'label' => 'mb.ogcapifeatures.admin.json_url',
            ])
        ;
    }
}
