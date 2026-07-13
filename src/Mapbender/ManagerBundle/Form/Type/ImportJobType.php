<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\FileType;
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('importFile', FileType::class, [
                'label' => 'mb.core.importjobtype.admin.importfile',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
        ;
    }

}
