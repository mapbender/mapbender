<?php


namespace Mapbender\ManagerBundle\Form\Type\Element;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FloatingAnchorType extends AbstractType
{
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => array(
                'mb.manager.admin.element.anchor.left-top' => 'left-top',
                'mb.manager.admin.element.anchor.left-bottom' => 'left-bottom',
                'mb.manager.admin.element.anchor.right-top' => 'right-top',
                'mb.manager.admin.element.anchor.right-bottom' => 'right-bottom',
            ),
            'label' => 'mb.manager.admin.element.anchor.label',
        ));
    }
}
