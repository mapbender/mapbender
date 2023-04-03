<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\SimpleSearch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Count;


class SimpleSearchAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('configurations', CollapsibleCollectionType::class, array(
                'label' => 'mb.core.simplesearch.admin.configurations',
                'allow_add' => true,
                'attr' => ['data-defaults' => json_encode(SimpleSearch::getDefaultChildConfiguration())],
                'allow_delete' => true,
                'initial_collapse_state' => CollapsibleCollectionType::INITIAL_STATE_ALL_COLLAPSED_EXCEPT_SINGLE_ENTRY,
                'entry_type' => SimpleSearchAdminConfigurationType::class,
                'error_bubbling' => false,
                'constraints' => [
                    new Count([
                        'min' => 1,
                        'minMessage' => 'mb.core.simplesearch.errors.no_configuration_added',
                    ]),
                ]
            ))
        ;
    }
}
