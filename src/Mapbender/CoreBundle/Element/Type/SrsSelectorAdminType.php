<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SrsSelectorAdminType extends AbstractType
{
    public function getName() {
        return 'srsselector';
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
//            'target' => null
            ));
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tooltip', 'text', array('required' => false))
                ->add('label', 'checkbox', array('required' => false))
                ->add('target_map', 'target_element', array(
                    'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                    'application' => $options['application'],
                    'property_path' => '[targets][map]',
                    'required' => false))
                ->add('target_coords', 'target_element', array(
                    'element_class' => 'Mapbender\\CoreBundle\\Element\\CoordinatesDisplay',
                    'application' => $options['application'],
                    'property_path' => '[targets][coordinatesdisplay]',
                    'required' => false)
                );
    }
}