<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Mapbender\ManagerBundle\Form\DataTransformer\ArrayToCsvScalarTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SketchAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('autoOpen', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.manager.autoOpen',
            ])
            ->add('deactivate_on_close', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.sketch.admin.deactivate_on_close',
            ])
            ->add('geometrytypes', ChoiceType::class, [
                'required' => true,
                'label' => 'mb.core.sketch.admin.geometrytypes',
                'multiple' => true,
                'choices' => [
                    'mb.core.sketch.geometrytype.point' => 'point',
                    'mb.core.sketch.geometrytype.line' => 'line',
                    'mb.core.sketch.geometrytype.polygon' => 'polygon',
                    'mb.core.sketch.geometrytype.rectangle' => 'rectangle',
                    'mb.core.sketch.geometrytype.circle' => 'circle',
                ],
            ])
            ->add('colors', TextType::class, [
                'required' => false,
                'label' => 'mb.core.sketch.admin.colors'
            ])
            ->add('allow_custom_color', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.sketch.admin.allow_custom_color'
            ])
        ;
        $builder->get('colors')->addModelTransformer(new ArrayToCsvScalarTransformer());
    }
}
