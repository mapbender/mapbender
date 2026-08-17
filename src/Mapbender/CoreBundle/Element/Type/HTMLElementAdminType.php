<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\HTMLElement;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Validator\Constraints\HtmlTwigConstraint;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;

class HTMLElementAdminType extends AbstractType implements EventSubscriberInterface
{
    use MapbenderTypeTrait;

    public function __construct(protected TranslatorInterface $trans)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('openInline', CheckboxType::class, $this->createInlineHelpText([
                'required' => false,
                'label' => 'mb.manager.element.openInline',
                'help' => 'mb.manager.element.openInlineHelp',
            ], $this->trans))
            // Temporary. Replaced in preSetData
            ->add('content', TextareaType::class, [
                'required' => true,
                'label' => 'mb.core.htmlelement.admin.content',
                'constraints' => [
                    new NotBlank(),
                    new HtmlTwigConstraint(),
                ],
            ])
            ->add('classes', TextType::class, [
                'required' => false,
                'label' => 'mb.core.htmlelement.admin.classes',
            ])
            ->add('autoOpen', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.manager.autoOpen',
            ])
            ->add('modal', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.htmlelement.admin.modal',
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
        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var Element $element */
        $element = $event->getForm()->getParent()->getData();
        $event->getForm()->add('content', TextareaType::class, [
            'required' => false,
            'label' => 'mb.core.htmlelement.admin.content',
            'constraints' => new HtmlTwigConstraint([
                // Same twig variable scope as frontend
                /** @see HTMLElement::getView */
                'entity' => $element,
                'application' => $element->getApplication(),
            ])
        ]);
    }
}
