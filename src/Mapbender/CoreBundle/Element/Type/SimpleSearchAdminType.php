<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class SimpleSearchAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('configurations', MapbenderCollectionType::class, array(
                'allow_add' => true,
                'allow_delete' => true,
                'allow_collapse' => true,
                'initial_collapse_state' => MapbenderCollectionType::INITIAL_STATE_ALL_COLLAPSED_EXCEPT_SINGLE_ENTRY,
                'entry_type' => SimpleSearchAdminConfigurationType::class,
            ))
        ;
    }
}
