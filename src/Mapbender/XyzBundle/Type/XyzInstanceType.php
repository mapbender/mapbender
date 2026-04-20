<?php

namespace Mapbender\XyzBundle\Type;

use Mapbender\ManagerBundle\Form\Type\SourceInstanceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class XyzInstanceType extends AbstractType
{

    public function getParent(): string
    {
        return SourceInstanceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('minZoom', IntegerType::class, [
                'required' => false,
                'label' => 'mb.xyz.admin.min_zoom',
                'attr' => ['placeholder' => '0'],
                'empty_data' => '0',
            ])
            ->add('maxZoom', IntegerType::class, [
                'required' => false,
                'label' => 'mb.xyz.admin.max_zoom',
                'attr' => ['placeholder' => '22'],
                'empty_data' => '22',
            ])
            ->add('selected', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.xyz.admin.selected',
            ])
            ->add('allowSelected', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.manager.source.instancelayer.allowselecttoc',
            ])
        ;
    }
}
