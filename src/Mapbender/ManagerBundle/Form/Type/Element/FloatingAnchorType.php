<?php


namespace Mapbender\ManagerBundle\Form\Type\Element;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpKernel\Kernel;
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
                'left-top'     => 'left-top',
                'left-bottom'  => 'left-bottom',
                'right-top'    => 'right-top',
                'right-bottom' => 'right-bottom',
            ),
        ));
        if (Kernel::MAJOR_VERSION < 3) {
            $resolver->setDefault('choices_as_values', true);
        }
    }
}
