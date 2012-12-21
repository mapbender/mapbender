<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CopyrightAdminType extends AbstractType
{
    public function getName() {
        return 'copyright';
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
                ->add('copyrigh_text', 'text', array('required' => false))
                ->add('dialog_link', 'text', array('required' => false))
                ->add('dialog_content', 'textarea', array('required' => false))
                ->add('dialog_title', 'text', array('required' => false));
    }
}