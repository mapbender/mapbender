<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScreentypeType extends AbstractType
{
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => array(
                'mb.manager.screentype.choice.all' => 'all',
                'mb.manager.screentype.choice.mobile' => 'mobile',
                'mb.manager.screentype.choice.desktop' => 'desktop',
            ),
            'required' => false,
            'placeholder' => false,
        ));
    }
}
