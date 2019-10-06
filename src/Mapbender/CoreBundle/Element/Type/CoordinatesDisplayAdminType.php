<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoordinatesDisplayAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('anchor', "choice",
                array(
                'required' => true,
                "choices" => array(
                    'left-top' => 'left-top',
                    'left-bottom' => 'left-bottom',
                    'right-top' => 'right-top',
                    'right-bottom' => 'right-bottom',
                ),
                'choices_as_values' => true,
            ))
            ->add('numDigits', 'integer', array('required' => true))
            ->add('label', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.corrdsdisplay.label', // sic
            ))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
            ->add('empty', 'text', array('required' => false, "trim" => false))
            ->add('prefix', 'text', array('required' => false, "trim" => false))
            ->add('separator', 'text',
                array('required' => false, "trim" => false))
        ;
    }

}
