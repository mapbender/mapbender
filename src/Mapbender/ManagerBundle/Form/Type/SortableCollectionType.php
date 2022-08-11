<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SortableCollectionType extends AbstractType implements EventSubscriberInterface
{
    public function getParent()
    {
        return CollectionType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['sortable'] = true;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SUBMIT => 'preSubmit',
        );
    }

    public function preSubmit(FormEvent $e)
    {
        // Reorder data in order of submitted form inputs (=document order)
        // and strip any non-numeric keys.
        $e->setData(\array_values($e->getData()));
        $e->getForm()->setData(\array_values($e->getForm()->getData()));
    }
}
