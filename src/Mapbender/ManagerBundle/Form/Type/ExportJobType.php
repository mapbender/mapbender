<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Mapbender\ManagerBundle\Component\ExchangeJob;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('application', 'entity', array(
                'label' => 'form.manager.admin.application.export.application',
                'class' => 'Mapbender\CoreBundle\Entity\Application',
                'property' => 'title',
                'multiple' => false,
                'choices' => $options['application'],
                'required' => true,
            ))
            ->add('format', 'choice',
                array(
                'required' => true,
                'choices' => array(
                    ExchangeJob::FORMAT_JSON => ExchangeJob::FORMAT_JSON,
                    ExchangeJob::FORMAT_YAML => ExchangeJob::FORMAT_YAML)));
    }

}
