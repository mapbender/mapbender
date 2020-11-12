<?php


namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ToolbarSettingsType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => true,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('screenType', 'Mapbender\ManagerBundle\Form\Type\ScreentypeType', array(
            'label' => 'mb.manager.screentype.label',
        ));
    }
}
