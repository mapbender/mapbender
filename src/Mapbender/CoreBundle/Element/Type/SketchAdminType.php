<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SketchAdminType extends AbstractType
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
        $types = array(
            "circle" => "circle",
        );
        $builder
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false,
            ))
            ->add('defaultType', 'choice', array(
                "required" => true,
                'choices_as_values' => true,
                "choices" => $types,
            ))
            ->add('types', 'choice',
                array("required" => true,
                "choices" => $types,
                'choices_as_values' => true,
                "multiple" => true,
            ))
        ;
    }

}