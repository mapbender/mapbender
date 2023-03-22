<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\ManagerBundle\Form\Type\SortableCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MapbenderCollectionType extends AbstractType
{
    public const INITIAL_STATE_ALL_COLLAPSED = 'all_collapsed';
    public const INITIAL_STATE_ALL_COLLAPSED_EXCEPT_SINGLE_ENTRY = 'all_collapsed_except_single';
    public const INITIAL_STATE_ALL_OPENED = 'all_opened';
    public const INITIAL_STATE_FIRST_OPENED = 'first_opened';
    public const INITIAL_STATE_LAST_OPENED = 'last_opened';

    public function getParent()
    {
        return SortableCollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allow_collapse' => true,
            'initial_collapse_state' => self::INITIAL_STATE_FIRST_OPENED,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, [
            'allow_collapse' => $options['allow_collapse'],
            'initial_collapse_state' => $options['initial_collapse_state'],
        ]);
    }

}
