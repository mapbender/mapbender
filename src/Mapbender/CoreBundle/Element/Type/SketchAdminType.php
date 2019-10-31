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
                'required' => false,
            ))
            // @todo: redundant, remove
            ->add('defaultType', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                "required" => true,
                'choices_as_values' => true,
                "choices" => $types,
            ))
            // @todo: redundant, remove
            ->add('types', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                "choices" => $types,
                'choices_as_values' => true,
                "multiple" => true,
            ))
        ;
    }

}
