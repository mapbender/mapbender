<?php


namespace Mapbender\CoreBundle\Form\Type\Template;


use Mapbender\CoreBundle\Entity\RegionProperties;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BaseToolbarType extends AbstractType
{
    public function getParent(): string
    {
        return RegionSettingsType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('item_alignment', ChoiceType::class, [
            'label' => 'mb.manager.toolbar.alignment.label',
            'choices' => [
                'mb.manager.toolbar.alignment.choice.left' => 'left',
                'mb.manager.toolbar.alignment.choice.right' => 'right',
                'mb.manager.toolbar.alignment.choice.center' => 'center',
            ],
        ]);
        $builder->add('generate_button_menu', ChoiceType::class, [
            'label' => 'mb.manager.toolbar.menu_button.label',
            'choices' => RegionProperties::responsiveOptions(),
            'required' => false,
            'placeholder' => false,
        ]);
        $builder->add('menu_label', TextType::class, [
            'required' => false,
            'label' => 'mb.manager.toolbar.menu_label',
        ]);
    }
}
