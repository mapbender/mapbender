<?php


namespace Mapbender\CoreBundle\Form\Type\Template;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BaseToolbarType extends AbstractType
{
    public function getParent()
    {
        return 'Mapbender\CoreBundle\Form\Type\Template\RegionSettingsType';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('item_alignment', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
            'label' => 'mb.manager.toolbar.alignment.label',
            'choices' => array(
                'mb.manager.toolbar.alignment.choice.left' => 'left',
                'mb.manager.toolbar.alignment.choice.right' => 'right',
                'mb.manager.toolbar.alignment.choice.center' => 'center',
            ),
        ));
        $builder->add('generate_button_menu', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
            'label' => 'mb.manager.toolbar.generate_button_menu',
        ));
        $builder->add('menu_label', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
            'required' => false,
            'label' => 'mb.manager.toolbar.menu_label',
        ));
    }
}
