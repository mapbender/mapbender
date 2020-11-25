<?php

namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\OptionsResolver;


class SidepaneSettingsType extends AbstractType
{
    protected $allowResponsiveContainers;

    public function __construct($allowResponsiveContainers)
    {
        $this->allowResponsiveContainers = $allowResponsiveContainers;
    }

    public function getParent()
    {
        return 'Mapbender\CoreBundle\Form\Type\Template\RegionSettingsType';
    }

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
        $builder->add('name', 'Mapbender\CoreBundle\Form\Type\Template\Fullscreen\SidepaneTypeType', array(
            'label' => 'mb.core.admin.template.sidepane.type',
        ));
        if ($this->allowResponsiveContainers) {
            $builder->add('screenType', 'Mapbender\ManagerBundle\Form\Type\ScreentypeType', array(
                'label' => 'mb.manager.screentype.label',
            ));
        }
        $builder->add('width', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
            'required' => false,
            'attr' => array(
                'placeholder' => '350px',   // HACK: this is implicitly the default (via CSS)
            ),
            'label' => 'mb.manager.sidepane.width',
        ));
        if (Kernel::MAJOR_VERSION <= 3) {
            $choiceDefaults = array(
                'choices_as_values' => true,
            );
        } else {
            $choiceDefaults = array();
        }
        $builder->add('align', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', $choiceDefaults + array(
            'required' => false,
            'choices' => array(
                'mb.manager.sidepane.align.choice.left' => 'left',
                'mb.manager.sidepane.align.choice.right' => 'right',
            ),
            'label' => 'mb.manager.sidepane.align.label',
            'placeholder' => false,
            'empty_data' => 'left',
        ));
        $builder->add('closed', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
            'required' => false,
            'label' => 'mb.manager.sidepane.closed',
        ));
    }
}
