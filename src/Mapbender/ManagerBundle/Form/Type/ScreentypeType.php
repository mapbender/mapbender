<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScreentypeType extends AbstractType
{
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                'mb.manager.screentype.choice.all' => 'all',
                'mb.manager.screentype.choice.mobile' => 'mobile',
                'mb.manager.screentype.choice.desktop' => 'desktop',
            ],
            'required' => false,
            'placeholder' => false,
        ]);
    }
}
