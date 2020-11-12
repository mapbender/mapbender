<?php

namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class SidepaneSettingsType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => true,
        ));
    }

    public function getBlockPrefix()
    {
        return 'sidepane_settings';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('screenType', 'Mapbender\ManagerBundle\Form\Type\ScreentypeType', array(
            'label' => 'mb.manager.screentype.label',
        ));
        $builder->add('name', 'Mapbender\CoreBundle\Form\Type\Template\Fullscreen\SidepaneTypeType');
    }
}
