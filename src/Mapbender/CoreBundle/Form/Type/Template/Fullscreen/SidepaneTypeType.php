<?php


namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for the type of sidepane in a fullscreen application.
 * NOTE: the entry for this in persisted RegionProperties is called "name".
 */
class SidepaneTypeType extends AbstractType
{
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'required' => false,
            'placeholder' => false,
            'choices' => array(
                'None' => '',
                'mb.manager.template.region.tabs.label' => 'tabs',
                'mb.manager.template.region.accordion.label' => 'accordion',
            ),
        ));
        if (Kernel::MAJOR_VERSION < 3) {
            $resolver->setDefaults(array(
                'choices_as_values' => true,
            ));
        }
    }
}
