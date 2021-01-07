<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ZoomBarAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
        ));
    }

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
                    "Zoom in/out" => "zoom_in_out",
                    "Zoom slider" => "zoom_slider",
                ),
                'choices_as_values' => true,
                'attr' => array(
                    'size' => 6,
                ),
            ))
            ->add('target', 'Mapbender\CoreBundle\Element\Type\TargetElementType', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
            ->add('anchor', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                "choices" => array(
                    'inline' => 'inline',
                    'left-top' => 'left-top',
                    'left-bottom' => 'left-bottom',
                    'right-top' => 'right-top',
                    'right-bottom' => 'right-bottom',
                ),
                'choices_as_values' => true,
            ))
            ->add('draggable', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.manager.admin.zoombar.draggable',
            ))
        ;
    }

}
