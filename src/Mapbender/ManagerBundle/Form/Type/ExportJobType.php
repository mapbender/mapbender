<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Mapbender\ManagerBundle\Component\ExchangeJob;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * ExportJobType class creates a form for an ExportJob object.
 */
class ExportJobType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'exportjob';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'applications' => array()
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('applications', 'entity',
                array(
                'class' => 'Mapbender\CoreBundle\Entity\Application',
                'property' => 'title',
                'multiple' => true,
                'empty_value' => 'All applications',
                'empty_data' => null))
            ->add('addSources', 'checkbox', array('required' => false))
            ->add('addAcl', 'checkbox', array('required' => false))
            ->add('format', 'choice',
                array(
                'required' => true,
                'choices' => array(
                    ExchangeJob::$FORMAT_JSON => ExchangeJob::$FORMAT_JSON,
                    ExchangeJob::$FORMAT_YAML => ExchangeJob::$FORMAT_YAML)));
    }

}
