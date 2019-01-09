<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ImportJobType class creates a form for an ImportJob object.
 */
class ImportJobType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'importjob';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array());
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('importFile', 'file', array('required' => true))
        ;
    }

}
