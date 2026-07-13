<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Mapbender\CoreBundle\Validator\Constraints\HtmlTwigConstraint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;

class CopyrightAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('autoOpen', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.manager.autoOpen',
            ])
            ->add('popupWidth', TextType::class, [
                'required' => false,
                'label' => 'mb.manager.popup_width',
                'attr' => [
                    'placeholder' => '300px',
                ],
                'constraints' => [
                    new Type('numeric'),
                    new PositiveOrZero(),
                ]
            ])
            ->add('popupHeight', TextType::class, [
                'required' => false,
                'label' => 'mb.manager.popup_height',
                'attr' => [
                    'placeholder' => 'mb.manager.automatic',
                ],
                'constraints' => [
                    new Type('numeric'),
                    new PositiveOrZero(),
                ],
            ])
            ->add('content', TextareaType::class, [
                'required' => true,
                'label' => 'mb.core.copyright.admin.content',
                'constraints' => [
                    new NotBlank(),
                    new HtmlTwigConstraint(),
                ],
            ])
            ->add('dontShowAgain', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.copyright.admin.dontShowAgain',
            ])
            ->add('dontShowAgainLabel', TextType::class, [
                'required' => false,
                'label' => 'mb.core.copyright.admin.dontShowAgainLabel',
                'data' => 'mb.core.copyright.admin.dontShowAgainDefaultLabel',
            ])
        ;
    }
}
