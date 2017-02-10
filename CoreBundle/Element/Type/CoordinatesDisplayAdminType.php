<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class CoordinatesDisplayAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'coordinatesdisplay';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
        $builder->add('tooltip', 'text', array('required' => false))
            ->add('anchor', "choice",
                array(
                'required' => true,
                "choices" => array(
                    'left-top' => 'left-top',
                    'left-bottom' => 'left-bottom',
                    'right-top' => 'right-top',
                    'right-bottom' => 'right-bottom')))
            ->add('numDigits', 'integer', array('required' => true))
            ->add('label', 'checkbox', array('required' => false))
            ->add('target', 'target_element',
                array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('empty', 'text', array('required' => false, "trim" => false))
            ->add('prefix', 'text', array('required' => false, "trim" => false))
            ->add('separator', 'text',
                array('required' => false, "trim" => false))
        ;
    }

}