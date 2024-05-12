<?php

namespace Mapbender\CoreBundle\Form\Type\Template\Fullscreen;


use Mapbender\CoreBundle\Element\Type\MapbenderTypeTrait;
use Mapbender\ManagerBundle\Form\Type\ScreentypeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;


class SidepaneSettingsType extends AbstractType
{
    use MapbenderTypeTrait;

    public function __construct(private TranslatorInterface $translator, private bool $allowResponsiveContainers)
    {
    }

    public function getParent(): string
    {
        return 'Mapbender\CoreBundle\Form\Type\Template\RegionSettingsType';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'compound' => true,
        ));
    }

    public function getBlockPrefix(): string
    {
        return 'sidepane_settings';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', SidepaneTypeType::class, array(
            'label' => 'mb.core.admin.template.sidepane.type.label',
        ));
        if ($this->allowResponsiveContainers) {
            $builder->add('screenType', ScreentypeType::class, array(
                'label' => 'mb.manager.screentype.label',
            ));
        }
        $builder->add('width', TextType::class, array(
            'required' => false,
            'attr' => array(
                'placeholder' => '350px',   // HACK: this is implicitly the default (via CSS)
            ),
            'label' => 'mb.manager.sidepane.width',
        ));
        $builder->add('resizable', CheckboxType::class, $this->createInlineHelpText([
            'required' => false,
            'label' => 'mb.manager.sidepane.resizable',
            'help' => 'mb.manager.sidepane.resizable_help',
        ], $this->translator, false));
        $builder->add('align', ChoiceType::class, array(
            'required' => false,
            'choices' => array(
                'mb.manager.sidepane.align.choice.left' => 'left',
                'mb.manager.sidepane.align.choice.right' => 'right',
            ),
            'label' => 'mb.manager.sidepane.align.label',
            'placeholder' => false,
            'empty_data' => 'left',
        ));
        $builder->add('closed', CheckboxType::class, array(
            'required' => false,
            'label' => 'mb.manager.sidepane.closed',
        ));
    }
}
