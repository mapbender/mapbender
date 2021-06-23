<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SketchAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('target', 'Mapbender\ManagerBundle\Form\Type\Element\MapTargetType')
            ->add('auto_activate', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.sketch.admin.auto_activate',
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
                    'mb.core.sketch.geometrytype.text' => 'text',
                ),
            ))
        ;
    }
}
