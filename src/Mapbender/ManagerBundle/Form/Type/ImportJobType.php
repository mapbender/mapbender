<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * ImportJobType class creates a form for an ImportJob object.
 */
class ImportJobType extends AbstractType
{

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
            ->add('importFile', 'Symfony\Component\Form\Extension\Core\Type\FileType', array(
                'constraints' => array(
                    new NotBlank(),
                ),
            ))
        ;
    }

}
