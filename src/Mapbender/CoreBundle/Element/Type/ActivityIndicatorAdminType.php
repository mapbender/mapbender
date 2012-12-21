<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ActivityIndicatorAdminType extends AbstractType
{
    public function getName() {
        return 'activityindicator';
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
                ->add('activityClass', 'text', array('required' => false))
                ->add('ajaxActivityClass', 'text', array('required' => false))
                ->add('tileActivityClass', 'text', array('required' => false));
    }
}