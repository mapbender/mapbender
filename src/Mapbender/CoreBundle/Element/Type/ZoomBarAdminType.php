<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ZoomBarAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('components', ChoiceType::class, [
                'required' => true,
                'label' => 'mb.core.zoombar.admin.components',
                'multiple' => true,
                'choices' => [
                    "mb.core.zoombar.admin.rotation" => "rotation",
                    "mb.core.zoombar.admin.zoommax" => "zoom_max",
                    'mb.core.zoombar.zoom_home' => 'zoom_home',
                    "mb.core.zoombar.admin.zoominout" => "zoom_in_out",
                    "mb.core.zoombar.admin.zoomslider" => "zoom_slider",
                ],
                'attr' => [
                    'size' => 5,
                ],
            ])
            ->add('zoomHomeRestoresLayers', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.zoombar.zoomHomeRestoresLayers',
            ])
            ->add('draggable', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.manager.admin.zoombar.draggable',
            ])
        ;
    }

}
