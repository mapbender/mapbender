<?php
namespace Mapbender\WmtsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

//use Mapbender\ManagerBundle\Form\Type\BaseElementType;

class WmtsSourceSimpleType extends AbstractType {
    
    public function getName() {
        return 'wmtssource';
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            // Base data
            ->add('originUrl', 'text', array(
                'required' => true,
                'attr' => array(
                    'title' => 'The wms GetCapabilities url.')))
            ->add('username', 'text', array(
                'required' => false,
                'attr' => array(
                    'title' => 'The usename.')))
            ->add('password', 'text', array(
                'required' => false,
                'attr' => array(
                    'title' => 'The password.')));
    }
}

