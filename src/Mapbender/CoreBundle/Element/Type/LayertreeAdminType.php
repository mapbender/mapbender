<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LayertreeAdminType extends AbstractType
{
    public function getName() {
        return 'toc';
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null
            ));
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
//                ->add('tooltip', 'text', array('required' => false))
                ->add('target', 'target_element', array(
                    'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                    'application' => $options['application'],
                    'property_path' => '[target]',
                    'required' => false))
                ->add('type', 'choice', array(
                    'required' => true,
                    'choices' => array('dialog' => 'Dialog', 'element' => 'Element')))
                ->add('autoOpen', 'checkbox', array(
                    'required' => false));
    }
}