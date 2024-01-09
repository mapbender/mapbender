<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ZoomBarAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('components', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                'label' => 'mb.core.zoombar.admin.components',
                'multiple' => true,
                'choices' => array(
                    "mb.core.zoombar.admin.rotation" => "rotation",
                    "mb.core.zoombar.admin.zoommax" => "zoom_max",
                    'mb.core.zoombar.zoom_home' => 'zoom_home',
                    "mb.core.zoombar.admin.zoominout" => "zoom_in_out",
                    "mb.core.zoombar.admin.zoomslider" => "zoom_slider",
                ),
                'attr' => array(
                    'size' => 5,
                ),
            ))
            ->add('zoomHomeRestoresLayers', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.zoombar.zoomHomeRestoresLayers',
            ))
            ->add('draggable', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.manager.admin.zoombar.draggable',
            ))
        ;
    }

}
