<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\ManagerBundle\Form\Type\SortableCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * An extension to Symfony's CollectionType that displays the collection items as a bootstrap accordion.
 * Each item can be expanded and collapsed individually, individual items can be reordered, deleted and duplicated.
 *
 * The option `initial_collapse_state` can be used to decide which items should be expanded or collapsed when loading a form
 * Note that items that contain an error and newly created items will always be expanded regardless of this setting
 */
class CollapsibleCollectionType extends AbstractType
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
            'initial_collapse_state' => self::INITIAL_STATE_ALL_COLLAPSED_EXCEPT_SINGLE_ENTRY,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, [
            'allow_collapse' => true,
            'initial_collapse_state' => $options['initial_collapse_state'],
        ]);
    }

}
