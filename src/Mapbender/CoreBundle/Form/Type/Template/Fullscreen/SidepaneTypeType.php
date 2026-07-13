<?php


namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for the type of sidepane in a fullscreen application.
 * NOTE: the entry for this in persisted RegionProperties is called "name".
 */
class SidepaneTypeType extends AbstractType
{
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'placeholder' => false,
            'choices' => [
                'mb.core.admin.template.sidepane.type.choice.tabs' => 'tabs',
                'mb.core.admin.template.sidepane.type.choice.accordion' => 'accordion',
                'mb.core.admin.template.sidepane.type.choice.list' => 'list',
                'mb.core.admin.template.sidepane.type.choice.unstyled' => '',
            ],
        ]);
    }
}
