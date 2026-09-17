<?php

namespace Mapbender\OgcApiFeaturesBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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

        if ($options['is_refresh']) {
            $builder
                ->add('activate_new_layers', CheckboxType::class, [
                    'required' => false,
                    'label' => 'mb.manager.source.activate_new_layers',
                ])
                ->add('select_new_layers', CheckboxType::class, [
                    'required' => false,
                    'label' => 'mb.manager.source.select_new_layers',
                ])
            ;
        }
    }
}
