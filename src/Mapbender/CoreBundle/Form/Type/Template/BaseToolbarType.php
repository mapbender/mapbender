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
            'label' => 'mb.manager.toolbar_alignment.label',
            'choices' => array(
                'mb.manager.toolbar_alignment.choice.left' => 'left',
                'mb.manager.toolbar_alignment.choice.right' => 'right',
                'mb.manager.toolbar_alignment.choice.center' => 'center',
            ),
        ));
    }
}
