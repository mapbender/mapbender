<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\ManagerBundle\Form\Type\SortableCollectionType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MapbenderCollectionType extends CollectionType
{
    public const INITIAL_STATE_ALL_COLLAPSED = 'all_collapsed';
    public const INITIAL_STATE_ALL_OPENED = 'all_opened';
    public const INITIAL_STATE_FIRST_OPENED = 'first_opened';

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'allow_collapse' => true,
            'initial_collapse_state' => self::INITIAL_STATE_FIRST_OPENED,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars = array_replace($view->vars, [
            'allow_collapse' => $options['allow_collapse'],
            'initial_collapse_state' => $options['initial_collapse_state'],
        ]);
    }

}
