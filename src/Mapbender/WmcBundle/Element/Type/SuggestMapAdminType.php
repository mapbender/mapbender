<?php

namespace Mapbender\WmcBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Paul Schmidt
 */
class SuggestMapAdminType extends AbstractType
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
        $builder
            ->add('tooltip', 'text', array('required' => false))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
            ->add('receiver', 'choice', array(
                'multiple' => true,
                'required' => true,
                'choices' => array(
                    'E-Mail' => 'email',
                    'Facebook' => 'facebook',
                    'Twitter' => 'twitter',
                    'Google+' => 'google+',
                ),
                'choices_as_values' => true,
            ))
        ;
    }

}
