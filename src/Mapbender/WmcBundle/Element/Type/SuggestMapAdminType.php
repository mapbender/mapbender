<?php

namespace Mapbender\WmcBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class SuggestMapAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'suggestmap';
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
                ->add('receiver', 'choice',
                      array(
                          'multiple' => true,
                          'required' => false,
                          'choices' => array(
                              'email' => 'e-mail',
                              'facebook' => 'facebook',
                              'twitter' => 'twitter')))
                ->add('target', 'target_element',
                        array(
                            'element_class' => 'Mapbender\\WmcBundle\\Element\\WmcHandler',
                            'application' => $options['application'],
                            'property_path' => '[target]',
                            'required' => false));
    }

}