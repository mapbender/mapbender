<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class SourceSetAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'sourcesset';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'sources' => array(" " => " ")
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text',
                      array(
                    'required' => true,
                    'property_path' => '[title]'))
                ->add('show', "checkbox", array('required' => false))
                ->add('sources', 'choice',
                      array(
                    'property_path' => '[sources]',
                    'choices' => $options['sources'],
                    'required' => false,
                    'multiple' => true));
    }

}