<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
