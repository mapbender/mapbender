<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\SimpleSearch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Contracts\Translation\TranslatorInterface;


class SimpleSearchAdminType extends AbstractType
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
