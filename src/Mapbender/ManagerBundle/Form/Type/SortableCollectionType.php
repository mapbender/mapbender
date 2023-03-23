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
            // Bump priority to run before collection events
            /** @see \Symfony\Component\Form\Extension\Core\Type\CollectionType::buildForm */
            /** @see \Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener::getSubscribedEvents */
            FormEvents::PRE_SET_DATA => ['preSetData', 1],
            FormEvents::PRE_SUBMIT => ['preSubmit', 1],
        );
    }

    public function preSetData(FormEvent $e)
    {
        // Reorder data in order of submitted form inputs (=document order)
        // and strip any non-numeric keys.
        $data = $e->getData();
        if ($data === null) return;
        $e->setData(\array_values($data));
    }

    public function preSubmit(FormEvent $e)
    {
        $data = $e->getData();
        if ($data === null) return;
        $e->setData(\array_values($data));
        $e->getForm()->setData(\array_values($e->getForm()->getData()));
    }
}
