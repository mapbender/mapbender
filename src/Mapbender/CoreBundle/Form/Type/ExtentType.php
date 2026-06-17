<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class ExtentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('0', NumberType::class, array(
                'label' => 'min x',
                'html5' => false,
                'constraints' => [
                    new NotBlank(),
                ]
            ))
            ->add('1', NumberType::class, array(
                'label' => 'min y',
                'html5' => false,
                'constraints' => [
                    new NotBlank(),
                ]
            ))
            ->add('2', NumberType::class, array(
                'label' => 'max x',
                'html5' => false,
                'constraints' => [
                    new NotBlank(),
                ]
            ))
            ->add('3', NumberType::class, array(
                'label' => 'max y',
                'html5' => false,
                'constraints' => [
                    new NotBlank(),
                ]
            ))
        ;
    }
}
