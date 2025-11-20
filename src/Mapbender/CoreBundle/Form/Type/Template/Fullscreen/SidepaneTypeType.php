<?php


namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;


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
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'required' => false,
            'placeholder' => false,
            'choices' => array(
                'mb.core.admin.template.sidepane.type.choice.tabs' => 'tabs',
                'mb.core.admin.template.sidepane.type.choice.accordion' => 'accordion',
                'mb.core.admin.template.sidepane.type.choice.list' => 'list',
                'mb.core.admin.template.sidepane.type.choice.unstyled' => '',
            ),
        ));
    }
}
