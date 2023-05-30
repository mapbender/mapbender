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
                'multiple' => true,
                'choices' => array(
                    "Rotation" => "rotation",
                    "zoom to max extent" => "zoom_max",
                    'mb.core.zoombar.zoom_home' => 'zoom_home',
                    "Zoom in/out" => "zoom_in_out",
                    "Zoom slider" => "zoom_slider",
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
