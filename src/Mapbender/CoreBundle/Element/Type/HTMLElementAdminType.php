<?php
namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\HTMLElement;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Validator\Constraints\HtmlTwigConstraint;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class HTMLElementAdminType extends AbstractType implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Temporary. Replaced in preSetData
            ->add('content', TextareaType::class, [
                'required' => false,
            ])
            ->add('classes', 'Symfony\Component\Form\Extension\Core\Type\TextType', [
                'required' => false,
            ])
        ;
        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents()
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
            'constraints' => new HtmlTwigConstraint(array(
                // Same twig variable scope as frontend
                /** @see HTMLElement::getView */
                'entity' => $element,
                'application' => $element->getApplication(),
            ))
        ));
    }
}
