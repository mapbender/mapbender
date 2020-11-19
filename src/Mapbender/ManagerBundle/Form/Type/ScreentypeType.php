<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpKernel\Kernel;
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
        ));

        if (Kernel::MAJOR_VERSION < 3) {
            $resolver->setDefaults(array(
                'choices_as_values' => true,
            ));
        }
    }
}
