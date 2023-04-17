<?php

namespace Mapbender\CoreBundle\Element\Type;

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('autoOpen', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.manager.autoOpen',
            ))
            ->add('deactivate_on_close', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.sketch.admin.deactivate_on_close',
            ))
            ->add('geometrytypes', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                'multiple' => true,
                'choices' => array(
                    'mb.core.sketch.geometrytype.point' => 'point',
                    'mb.core.sketch.geometrytype.line' => 'line',
                    'mb.core.sketch.geometrytype.polygon' => 'polygon',
                    'mb.core.sketch.geometrytype.rectangle' => 'rectangle',
                    'mb.core.sketch.geometrytype.circle' => 'circle',
                ),
            ))
            ->add('colors', TextType::class, array(
                'required' => false,
                'label' => 'mb.core.sketch.admin.colors'
            ))
            ->add('allow_custom_color', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.sketch.admin.allow_custom_color'
            ))
        ;
        $builder->get('colors')->addModelTransformer(new ArrayToCsvScalarTransformer());
    }
}
