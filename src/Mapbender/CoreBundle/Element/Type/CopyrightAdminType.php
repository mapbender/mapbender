<?php
namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Validator\Constraints\HtmlTwigConstraint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\TypeValidator;

class CopyrightAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('autoOpen', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.manager.autoOpen',
            ))
            ->add('popupWidth', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.manager.popup_width',
                'attr' => array(
                    'placeholder' => '300px',
                ),
                'constraints' => [
                    new Type('numeric'),
                    new PositiveOrZero(),
                ]
            ))
            ->add('popupHeight', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.manager.popup_height',
                'attr' => array(
                    'placeholder' => 'mb.manager.automatic',
                ),
                'constraints' => [
                    new Type('numeric'),
                    new PositiveOrZero(),
                ],
            ))
            ->add('content', 'Symfony\Component\Form\Extension\Core\Type\TextareaType', array(
                'required' => true,
                'label' => 'mb.core.copyright.admin.content',
                'constraints' => array(
                    new NotBlank(),
                    new HtmlTwigConstraint(),
                ),
            ))
            ->add('dontShowAgain', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.copyright.admin.dontShowAgain',
            ))
            ->add('dontShowAgainLabel', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.core.copyright.admin.dontShowAgainLabel',
                'data' => 'mb.core.copyright.admin.dontShowAgainDefaultLabel',
            ))
        ;
    }
}
