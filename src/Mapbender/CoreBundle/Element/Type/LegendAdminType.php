<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LegendAdminType extends AbstractType
{
    public function getName() {
        return 'legend';
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null
        ));
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tooltip', 'text', array('required' => false))
                ->add('elementType', 'choice', array(
                    'required' => true,
                    'choices' => array(
                        "dialog" => "dialog",
                        "blockelement" => "blockelement")))
                ->add('autoOpen', 'checkbox', array('required' => false))
                ->add('displayType', 'choice', array(
                    'required' => true,
                    'choices' => array(
                        "accordion" => "accordion",
                        "list" => "list")))
                ->add('target', 'target_element', array(
                    'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                    'application' => $options['application'],
                    'property_path' => '[target]',
                    'required' => false))
//                ->add('dialogtitle', 'text', array('required' => false))
                ->add('nolegend', 'text', array('required' => false))
                ;
        
    }
}