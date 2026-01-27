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
                'required' => false,
                'label' => 'mb.core.htmlelement.admin.content',
            ])
            ->add('classes', TextType::class, [
                'required' => false,
                'label' => 'mb.core.htmlelement.admin.classes',
            ])
        ;
        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
        );
    }

    public function preSetData(FormEvent $event)
    {
        /** @var Element $element */
        $element = $event->getForm()->getParent()->getData();
        $event->getForm()->add('content', TextareaType::class, array(
            'required' => false,
            'label' => 'mb.core.htmlelement.admin.content',
            'constraints' => new HtmlTwigConstraint(array(
                // Same twig variable scope as frontend
                /** @see HTMLElement::getView */
                'entity' => $element,
                'application' => $element->getApplication(),
            ))
        ));
    }
}
